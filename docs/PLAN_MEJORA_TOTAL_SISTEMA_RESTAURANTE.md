# Plan de mejora total — Sistema Restaurante

**Fecha del diagnóstico:** 2026-08-26  
**Rama analizada:** `feature/sistema-completo`  
**Base de datos analizada:** `idactivo_cafeteq (3).sql` (MySQL 5.7.23-23, dump del 2026-08-26)  
**Objetivo:** dejar un sistema sencillo y confiable para Caja y Cocina, con administración suficiente para operar un negocio real y una plataforma multi-negocio segura.

## 1. Resumen ejecutivo

El repositorio ya contiene una base considerable y no debe rehacerse desde cero. Caja incluye turnos, PIN, pagos mixtos, descuentos, propinas, movimientos, tickets y cierre; Cocina tiene una cola web; Administración cubre menú, inventario, pedidos, finanzas, clientes, promociones, facturación y personal.

El sistema todavía no puede considerarse completo ni listo para producción porque el flujo central presenta inconsistencias comprobables en el dump real:

- Una venta POS de $335 generó su pago dentro del turno, pero el pedido quedó como `pendiente`, sin turno, cajero, método ni fecha de pago. El cierre guardó $335 y cero pedidos vendidos.
- La detección dinámica de columnas de `RestPedidoModel` usa `SHOW COLUMNS ... LIKE ?`, una forma que falla con la configuración PDO nativa de este proyecto; al capturar la excepción, el modelo omite silenciosamente las columnas opcionales del pedido.
- La API móvil acepta el campo `pagado` enviado por el cliente y lo convierte directamente en `pagado_at`. Un pedido en efectivo puede quedar identificado como prepagado sin prueba de pago.
- La creación de pedidos móviles no autentica al usuario móvil: recibe `usuario_id` del cuerpo y permite CORS global.
- El POS marca una venta de mostrador como `entregado` al cobrar; por diseño nunca llega a Cocina.
- Caja permite cobrar/entregar pedidos de la app sin exigir que Cocina los haya marcado como `listo`.
- Cocina modifica estados mediante POST sin CSRF, recarga la página completa cada 20 segundos y no verifica correctamente respuestas HTTP fallidas.
- Las recetas de prueba no son operativas: solo dos de seis platillos tienen receta y sus cantidades están en `0.000`; por tanto, el inventario no representa el consumo real.
- El esquema tiene 71 tablas, pero solo 13 restricciones `FOREIGN KEY`; pedidos, partidas, pagos y turnos no tienen integridad referencial suficiente.
- Persisten módulos y rutas del modelo anterior —mesas, visitas, reservaciones, tickets y alertas— aunque esas tablas no existen en el dump marketplace.
- Hay archivos temporales y de diagnóstico desplegables desde web, incluida una clave fija de depuración y un generador público de hashes.
- No existe una suite automatizada de pruebas ni integración continua.

La estrategia recomendada es conservar lo valioso, corregir primero la integridad transaccional y después rediseñar los flujos por rol.

## 2. Estado actual por módulo

| Área | Estado | Evaluación |
|---|---|---|
| Caja / POS | Base avanzada | Funcionalidad amplia, pero el registro del pedido y el flujo hacia Cocina están rotos por la detección de columnas y los estados actuales. |
| Cocina / KDS | MVP básico | Sirve para una demostración; falta robustez, legibilidad a distancia, sonido, estaciones, sincronización incremental y control de transiciones. |
| Administración | Amplio pero inconsistente | Hay muchas pantallas útiles; falta consolidar el diseño, eliminar legado, cerrar configuración y orientar el dashboard a acciones diarias. |
| Superadmin | Esqueleto | Solo cubre dashboard, listado, alta y suspensión de negocios. Faltan planes, comisiones, soporte, auditoría, configuración y operación global. |
| Pedidos móviles | Riesgo crítico | Falta autenticación fuerte y el servidor confía en el indicador de pago recibido desde el cliente. |
| Inventario | Estructura presente | No puede ser confiable mientras recetas y momentos de descuento no estén completos e idempotentes. |
| Finanzas / cortes | Parcial | Existen reportes y cortes, pero la inconsistencia pedido–pago–turno ya produce cierres incorrectos. |
| Seguridad | Insuficiente para producción | Sesiones excesivamente largas, POST sensibles sin CSRF y utilidades de depuración públicas. |
| Calidad / despliegue | Insuficiente | El código pasa revisión sintáctica, pero no hay pruebas de negocio, CI ni ambiente reproducible con datos saneados. |

## 3. Principios UX del rediseño

1. **Una pantalla, una tarea principal.** Caja vende/cobra; Cocina prepara; Entrega despacha; Admin configura y supervisa.
2. **El estado se entiende sin leer manuales.** Color, texto e icono siempre juntos; nunca depender solo del color.
3. **Acciones frecuentes a un toque.** Objetivos táctiles mínimos de 48 px en Caja y 56 px en Cocina.
4. **Errores prevenidos antes de confirmar.** No permitir entregar un pedido no listo, cobrar menos del total o cerrar un turno con datos incoherentes.
5. **Información excepcional primero.** Notas, alergias, exclusiones, retrasos y diferencias de caja deben dominar visualmente.
6. **Confirmación clara y reversible.** Toda acción sensible muestra resultado, responsable y, cuando aplique, flujo controlado de reversa.
7. **El sistema funciona con presión operativa.** Letras grandes, pocas decisiones por paso, atajos de teclado y tolerancia a doble clic/reintentos.
8. **Misma gramática en todos los módulos.** Un solo sistema de diseño, mismos nombres de estados, botones, avisos, filtros y formatos monetarios.

## 4. Flujo operativo objetivo

### 4.1 Venta de mostrador

1. Cajero selecciona productos, modificadores, cantidad y notas.
2. Selecciona tipo: para llevar, recoger o consumo inmediato si el negocio lo habilita.
3. Cobra con uno o varios métodos.
4. El servidor crea atómicamente pedido, partidas, pago y relación con el turno.
5. Si algún producto requiere preparación, el pedido queda `pendiente` y aparece en Cocina.
6. Si todos los productos son de entrega inmediata, pasa a `listo`.
7. Cocina inicia y termina las partidas; el pedido cambia automáticamente a `listo`.
8. Caja/Entrega ve “Listos para entregar”, confirma entrega e imprime/reimprime el ticket.

Texto principal recomendado en el POS: **“Cobrar y enviar a cocina”**, o **“Cobrar y dejar listo”** cuando no requiere preparación.

### 4.2 Pedido de la app

1. La app crea el pedido autenticada como usuario móvil.
2. El servidor calcula precios; nunca acepta subtotales, descuentos o condición de pago como verdad del cliente.
3. Tarjeta/Stripe solo queda pagada después de confirmación verificable del proveedor o webhook idempotente.
4. Efectivo al recoger queda con pago `pendiente`.
5. Cocina recibe el pedido independientemente del método de pago, según la política configurada del negocio.
6. Caja cobra los pedidos en efectivo y solo entrega pedidos `listo`.
7. El pedido entregado queda ligado al turno que realizó el cobro o la entrega.

### 4.3 Cocina

La vista se organiza en tres columnas: **Nuevos**, **En preparación** y **Listos**.

- Tarjeta grande con folio, canal, hora prometida y cronómetro.
- Notas y exclusiones en bloque amarillo de alto contraste.
- Alérgenos en bloque rojo, cuando existan.
- Botones grandes “Iniciar” y “Listo”.
- Sonido solo para pedidos nuevos y control visible de silencio.
- Sin recarga completa: actualización incremental y conservación de scroll/foco.
- Indicador “Sin conexión / reconectando / actualizado hace N s”.
- Filtros por estación configurada: cocina, bebidas, empaque u otra.
- Cocina no entrega ni cobra; únicamente prepara y marca listo.

### 4.4 Entrega y caja

La bandeja de pedidos debe separarse por estado operativo, no solamente por condición de pago:

- **Por cobrar**
- **En preparación**
- **Listos para entregar**
- **Entregados recientemente**

Un pedido puede estar pagado y todavía no listo. La UI debe mostrar ambos estados de manera independiente.

### 4.5 Administración diaria

El dashboard debe responder primero a “¿qué necesita atención?” y después mostrar métricas:

- pedidos retrasados;
- diferencias de caja;
- reembolsos pendientes;
- productos agotados o con stock bajo;
- productos sin receta o receta incompleta;
- cuentas de staff bloqueadas;
- facturas o pagos pendientes.

## 5. Modelo de estados y datos recomendado

### 5.1 Separar operación y pago

Mantener `rest_pedidos.estado` como estado operativo:

`pendiente → en_preparacion → listo → entregado`

Rutas laterales controladas:

`pendiente/en_preparacion/listo → cancelado`

Agregar o formalizar `estado_pago`:

`pendiente | parcial | pagado | reembolso_pendiente | reembolsado | fallido`

`pagado_at` será una evidencia derivada del pago confirmado, no una bandera recibida del navegador o app.

### 5.2 Campos y catálogos mínimos

- `rest_platillos.requiere_preparacion`.
- `rest_platillos.estacion_id` o relación producto–estación.
- `rest_estaciones` por restaurante.
- `rest_pedidos.estado_pago`.
- `rest_pedidos.version` para actualizaciones concurrentes optimistas.
- `rest_pedido_eventos` para auditoría de cambios de estado.
- `rest_inventario_aplicaciones` o referencia única en movimientos para garantizar descuento/reversa idempotentes.
- Opcional en una fase posterior: `caja_terminales` para identificar físicamente cada terminal.

### 5.3 Integridad referencial

Después de auditar y reparar huérfanos, agregar relaciones para:

- pedido → restaurante;
- partida → pedido y platillo;
- modificador de partida → partida y modificador;
- pago → pedido, turno y cajero;
- turno → restaurante y cajero;
- movimiento de caja → turno;
- receta → platillo;
- ingrediente de receta → receta e ingrediente.

Las eliminaciones de información transaccional deben ser lógicas o restringidas; nunca cascadas sobre ventas históricas.

## 6. Plan por fases

### Fase 0 — Contención y seguridad (P0)

Objetivo: impedir exposición y operaciones financieras falsas.

- Retirar del despliegue `_tmp_generar_hash.php`, `public/debug-auth.php`, `public/test-email.php`, `api/test.php` y `test-routing.php`.
- Rotar cualquier clave de diagnóstico si los archivos llegaron al servidor.
- Convertir suspensión/reactivación y alta de Superadmin a POST con CSRF.
- Agregar CSRF a Cocina y validar método HTTP/respuesta JSON.
- Regenerar el ID de sesión después de todo login.
- Reducir la sesión administrativa de 10 años a una política razonable; Caja usa bloqueo rápido sin perder el turno.
- Añadir encabezados CSP, `frame-ancestors`, `nosniff`, política de referrer y caché privada para portales autenticados.
- Proteger la creación/consulta de pedidos móviles con token real de sesión móvil y comprobar que el usuario del token coincide con el pedido.
- Eliminar la confianza en `body.pagado`; solo el servidor/proveedor de pago cambia el estado financiero.

**Criterio de salida:** ningún endpoint sensible cambia estado por GET, sin CSRF o sin autenticación/autorización apropiada.

### Fase 1 — Reparación del núcleo transaccional (P0)

- Reemplazar `SHOW COLUMNS ... LIKE ?` por lectura completa segura del esquema o `information_schema.COLUMNS`.
- Añadir una prueba que demuestre que `estado`, `turno_caja_id`, `cajero_id`, `metodo_pago`, `pagado_at` y `pos_client_uuid` sí se persisten.
- Crear un servicio único de pedido/cobro para que el flujo web, POS y móvil compartan reglas.
- Ejecutar pedido, partidas, pagos, wallet y asignación de turno dentro de una sola transacción.
- Implementar máquina de estados y bloquear saltos inválidos.
- Caja no puede entregar antes de `listo`, salvo anulación explícita de Admin auditada.
- Evitar doble entrega, doble descuento de inventario y doble cierre mediante actualización condicional e idempotencia.
- Hacer transaccional el cierre y su espejo en `rest_cortes`, o guardar una cola de reintento si el espejo falla.

**Criterio de salida:** una venta de prueba produce exactamente un pedido, un conjunto de partidas, uno o más pagos y un turno consistente; el cierre coincide centavo por centavo.

### Fase 2 — Saneamiento y migración de datos (P0)

- Respaldar la base antes de corregir datos.
- Identificar pedidos con pago pero sin turno/cajero/fecha/método.
- Recalcular snapshots de turnos afectados desde `rest_pedido_pagos`.
- Revisar pedidos en efectivo marcados pagados sin movimiento de pago confirmado.
- Recalcular subtotales de partidas móviles que hoy están en cero.
- Detectar partidas huérfanas, pagos huérfanos y referencias cruzadas entre restaurantes.
- Marcar recetas incompletas; prohibir activar descuento automático si una cantidad es cero o la unidad no es convertible.
- Agregar restricciones e índices después del saneamiento.
- Generar un informe antes/después con conteos y sumas de control.

**Criterio de salida:** no hay huérfanos críticos y las sumas pedido–pago–turno–corte son reconciliables.

### Fase 3 — Rediseño de Caja (P1)

- Conservar el patrón de catálogo + carrito, que es adecuado.
- Mostrar claramente canal, tipo de entrega, cliente y destino del pedido.
- Cambiar el CTA según preparación: “Cobrar y enviar a cocina” / “Cobrar y dejar listo”.
- Añadir bandeja persistente de pedidos con las cuatro secciones operativas.
- Reemplazar `alert`, `prompt` y `confirm` por modales accesibles y trazables.
- Mostrar estado de red, último refresco y errores recuperables sin perder el carrito.
- Mejorar edición de partidas: cantidad, modificadores y nota desde la misma línea.
- Añadir modo compacto para teclado y modo táctil para tablet.
- Confirmación de cobro con cambio dominante y botón “Nueva venta” enfocado automáticamente.
- Flujo de cancelación/devolución con resumen del impacto por método.
- Configuración y prueba guiada de impresora 58/80 mm.

**Criterio de salida:** un cajero nuevo completa venta, pedido de app, devolución y cierre sin capacitación extensa ni intervención de Admin.

### Fase 4 — Rediseño de Cocina (P1)

- Extraer CSS y JS inline a módulos propios.
- Implementar tablero por estados con tipografía y controles aptos para distancia/guantes.
- Actualización incremental, sonido de nuevo pedido y reintentos visibles.
- Estaciones configurables y productos asignables a una estación.
- Estados por partida y agregación automática del pedido.
- Tiempo prometido, retraso configurable y orden por prioridad real.
- Pantalla completa, modo claro/oscuro y opción “mantener pantalla encendida” donde el navegador lo permita.
- Historial corto de pedidos listos y posibilidad controlada de deshacer el último avance.

**Criterio de salida:** ningún pedido nuevo pasa desapercibido; Cocina puede operar sin recargar la página y sin acciones de cobro/entrega.

### Fase 5 — Inventario y costo confiable (P1)

- Asistente de receta con validación de unidades y cantidades mayores que cero.
- Indicador por producto: receta completa, incompleta o sin receta.
- Elegir y documentar el momento de consumo. Recomendado: aplicar al iniciar preparación; cancelación posterior genera reversa o merma auditada.
- Libro de movimientos inmutable con referencia única al pedido.
- Conteo físico, ajuste, merma, compra y devolución con motivos normalizados.
- Costo teórico por platillo, margen y alertas por aumento de costo.
- Importación/exportación CSV para altas masivas.

**Criterio de salida:** una orden descuenta una sola vez las cantidades correctas y puede rastrearse hasta cada movimiento.

### Fase 6 — Administración y Superadmin (P2)

- Crear un sistema de diseño compartido y retirar estilos inline gradualmente.
- Reorganizar Admin en: Operación, Menú, Inventario, Clientes, Finanzas y Configuración.
- Completar onboarding con prueba de pedido y checklist de preparación real.
- Gestión de PIN, estaciones, permisos y bloqueo de staff.
- Reembolsos pendientes y conciliación de pagos.
- Superadmin: detalle de negocio, planes, comisiones, soporte, auditoría, configuración global, salud de integraciones e impersonación auditada.
- Paginación y filtros de servidor en listados globales.

**Criterio de salida:** Admin puede configurar y operar un negocio sin tocar SQL; Superadmin puede soportarlo sin acceder manualmente a la base.

### Fase 7 — Limpieza de arquitectura (P2)

- Elegir una sola identidad de producto y retirar nombres mezclados de CarniHub, Jungle Pizza, AMARE y CapiRest donde no sean integraciones reales.
- Actualizar README, instalación y mapa de módulos.
- Eliminar o aislar definitivamente mesas, visitas, reservaciones y tickets si el producto seguirá siendo marketplace/pickup.
- Consolidar tablas duplicadas de modificadores y clientes con migración compatible.
- Dividir `ApiController.php` en controladores por dominio.
- Crear repositorios/servicios para pedidos, pagos, inventario y esquema; evitar SQL de negocio en controladores y vistas.
- Versionar todas las migraciones desde un esquema base reproducible y añadir tabla de control de migraciones.

**Criterio de salida:** un entorno nuevo se instala de forma reproducible y no ejecuta rutas contra tablas inexistentes.

### Fase 8 — Pruebas, observabilidad y salida (P0 transversal)

- PHPUnit para precios, modificadores, descuentos, propinas, IVA, pagos mixtos y estados.
- Pruebas de integración con una base temporal MySQL 5.7/8 compatible.
- Prueba E2E crítica: alta de negocio → menú/receta → abrir caja → vender → preparar → entregar → inventario → cerrar → finanzas.
- Pruebas de aislamiento entre dos restaurantes.
- Pruebas de autorización de API móvil, CSRF y reintentos idempotentes.
- CI con lint PHP, sintaxis JS, migraciones y pruebas.
- Logs estructurados con request ID, usuario, restaurante y acción, sin secretos ni datos de tarjeta.
- Monitoreo de pedidos atorados, webhooks fallidos, diferencias de caja y errores de impresión.
- Piloto en una terminal y una pantalla de Cocina antes de despliegue general.

**Criterio de salida:** todos los recorridos críticos pasan automáticamente y existe un procedimiento documentado de respaldo, rollback y soporte.

## 7. Backlog priorizado inicial

| Orden | Prioridad | Entregable |
|---:|---|---|
| 1 | P0 | Corregir detección de columnas y agregar prueba de persistencia POS. |
| 2 | P0 | Autenticar pedidos móviles y retirar `body.pagado` como autoridad. |
| 3 | P0 | Saneamiento de la venta $335, pedidos cash/prepagados y snapshots de turnos. |
| 4 | P0 | Seguridad: diagnósticos, CSRF, sesión y acciones GET. |
| 5 | P0 | Máquina de estados y separación `estado` / `estado_pago`. |
| 6 | P1 | Venta POS enviada a Cocina y bandeja de listos para entrega. |
| 7 | P1 | KDS de tres columnas, sonido y actualización incremental. |
| 8 | P1 | Recetas e inventario idempotente. |
| 9 | P1 | Pruebas end-to-end y reconciliación financiera. |
| 10 | P2 | Sistema visual unificado, Admin orientado a tareas y Superadmin completo. |
| 11 | P2 | Retiro de legado y separación del controlador API monolítico. |

## 8. Matriz mínima de aceptación

| Escenario | Resultado obligatorio |
|---|---|
| Venta POS en efectivo | Pedido ligado al turno/cajero, pago confirmado, cambio correcto y envío a Cocina si aplica. |
| Venta POS con pago mixto | Suma exacta, un registro por método y cierre correcto. |
| Pedido app con efectivo | Permanece no pagado hasta que Caja cobre. |
| Pedido app con tarjeta | Solo se paga tras confirmación verificable del proveedor. |
| Cocina | Solo transiciones válidas; notas/exclusiones visibles; pedido listo cuando todas sus partidas aplicables estén listas. |
| Entrega | Imposible antes de `listo`, salvo override auditado. |
| Cancelación | Reversa financiera e inventario coherentes, motivo y responsable obligatorios. |
| Inventario | Descuento único e identificable por pedido; sin receta no se inventan consumos. |
| Cierre | Efectivo esperado, contado, diferencia y ventas coinciden con movimientos reales. |
| Multi-negocio | Ningún usuario o ID de otro restaurante permite consultar o modificar datos ajenos. |
| Reintento | Doble clic o timeout no duplica pedido, pago, inventario ni entrega. |

## 9. Decisiones que deben mantenerse

- Los precios visibles ya incluyen IVA; el desglose es informativo.
- Pagos mixtos siguen permitidos.
- El ticket HTML 58/80 mm permanece como opción universal; integración silenciosa de impresora es posterior.
- Cancelaciones y autorizaciones sensibles siempre quedan auditadas.
- El esquema se resuelve por `roles.slug`, nunca por ID fijo.
- Caja puede bloquear pantalla sin perder el turno, pero no mantener una sesión administrativa válida por años.

## 10. Qué significa “sistema completo” para la primera salida

La primera versión completa no necesita abarcar todas las ideas del marketplace. Sí debe cumplir sin excepción:

1. acceso seguro por rol;
2. configuración de negocio, menú, personal, métodos de pago e inventario;
3. pedido desde Caja o app con cálculo en servidor;
4. pago verificable y conciliable;
5. preparación clara en Cocina;
6. entrega controlada;
7. descuento/reversa de inventario idempotente;
8. apertura y cierre de caja exactos;
9. reportes financieros basados en una sola fuente de verdad;
10. respaldo, auditoría, pruebas automáticas y procedimiento de despliegue.

Todo módulo adicional debe entrar después de estabilizar este circuito central.
