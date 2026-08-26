# Correcciones pendientes — Sistema Restaurante

**Fecha:** 2026-08-26  
**Rama:** `feature/sistema-completo`  
**Fuente de verdad:** código actual + `idactivo_cafeteq (3).sql`  
**Objetivo:** corregir primero los riesgos de seguridad, cobro, pedidos, Cocina, inventario y cortes antes del retoque visual final.

> Este documento es un checklist de ejecución. El plan estratégico y de UX está en
> [`PLAN_MEJORA_TOTAL_SISTEMA_RESTAURANTE.md`](PLAN_MEJORA_TOTAL_SISTEMA_RESTAURANTE.md).

## 1. Bloqueadores críticos (P0)

### 1.1 Corregir la detección de columnas de pedidos

**Problema comprobado**

`RestPedidoModel::hasColumnInTable()` ejecuta `SHOW COLUMNS FROM ... LIKE ?`. Con las consultas preparadas nativas configuradas en PDO, esta detección puede fallar. La excepción se oculta y el método devuelve `false`, por lo que el modelo omite columnas que sí existen.

Esto explica por qué la venta POS de prueba por $335 creó el pago, pero el pedido quedó:

- `estado = pendiente`;
- `turno_caja_id = NULL`;
- `cajero_id = NULL`;
- `metodo_pago = NULL`;
- `pagado_at = NULL`;
- `pos_client_uuid = NULL`.

**Archivos**

- `app/models/RestPedidoModel.php`
- Como referencia correcta: `app/models/RestCuentaPendienteModel.php`
- Como referencia correcta: `app/models/RestInventarioModel.php`

**Corrección**

- Leer `SHOW COLUMNS FROM tabla` sin placeholder y construir un caché completo; o consultar `information_schema.COLUMNS`.
- Centralizar esta capacidad en `BaseModel` para evitar diferentes implementaciones.
- No ocultar errores de esquema críticos en producción sin registrar tabla y columna.

**Prueba obligatoria**

Crear una venta POS y comprobar que todas las columnas anteriores se guardan. Repetir el mismo UUID y comprobar que no se crea otro pedido ni otro pago.

- [ ] Código corregido
- [ ] Prueba automatizada agregada
- [ ] Prueba contra MySQL 5.7 realizada

### 1.2 No confiar en `pagado` enviado por la aplicación móvil

**Problema comprobado**

`ApiController::v1CrearRestPedidoMobile()` convierte directamente `body.pagado` en `pagado_at`. Un cliente puede enviar `pagado=true`, incluso si seleccionó efectivo.

**Archivo**

- `app/controllers/ApiController.php`

**Corrección**

- Eliminar `pagado` del contrato de creación de pedido.
- Efectivo al recoger siempre inicia como pago pendiente.
- Tarjeta solo cambia a pagado después de validar el Payment Intent o procesar un webhook Stripe auténtico e idempotente.
- Agregar `estado_pago` al pedido: `pendiente`, `parcial`, `pagado`, `reembolso_pendiente`, `reembolsado`, `fallido`.
- `pagado_at` solo se asigna desde el servicio de pagos del servidor.

**Prueba obligatoria**

Enviar `pagado=true` manualmente en un pedido de efectivo y comprobar que el servidor lo ignora y lo mantiene pendiente de cobro.

- [ ] Contrato móvil corregido
- [ ] Validación Stripe implementada
- [ ] Pruebas de falsificación de pago agregadas

### 1.3 Autenticar la creación y consulta de pedidos móviles

**Problema comprobado**

El endpoint `POST /api/v1/rest-pedidos` acepta un `usuario_id` del cuerpo sin verificar que pertenezca a una sesión móvil autenticada. Además, el subrouter usa CORS global.

**Archivos**

- `app/controllers/ApiController.php`
- Tablas `mobile_sesiones` o el mecanismo de tokens móviles vigente

**Corrección**

- Exigir Bearer token móvil válido.
- Obtener `mobile_usuario_id` desde el token, nunca desde el cuerpo.
- En consulta, comprobar que el pedido pertenece al usuario autenticado.
- Limitar CORS a los orígenes autorizados o documentar por qué la app nativa no necesita CORS abierto.
- Aplicar rate limit por usuario, IP y restaurante.

**Prueba obligatoria**

Un usuario A no puede crear ni consultar pedidos como usuario B. Una petición sin token recibe `401`.

- [ ] Autenticación móvil obligatoria
- [ ] Aislamiento entre usuarios probado
- [ ] Rate limit probado

### 1.4 Retirar archivos temporales y de diagnóstico

**Archivos que no deben quedar públicos**

- `_tmp_generar_hash.php`
- `public/debug-auth.php`
- `public/test-email.php`
- `api/test.php`
- `test-routing.php`

**Corrección**

- Retirarlos del despliegue y del commit local pendiente.
- Rotar la clave fija de depuración si el archivo llegó al servidor.
- Mover diagnósticos necesarios a comandos CLI protegidos y no accesibles por HTTP.
- Añadir patrones correspondientes al proceso de revisión/despliegue.

- [ ] Archivos retirados
- [ ] Claves rotadas
- [ ] Despliegue verificado

### 1.5 Proteger todas las operaciones sensibles con POST y CSRF

**Problemas**

- Cocina cambia estados por POST sin validar CSRF.
- Superadmin suspende/reactiva negocios mediante GET.
- Alta de negocio no valida CSRF.

**Archivos**

- `app/controllers/RestCocinaController.php`
- `app/views/cocina/index.php`
- `app/controllers/SuperadminController.php`
- `app/views/superadmin/negocios.php`
- `app/views/superadmin/negocio_form.php`

**Corrección**

- Exigir POST en cada mutación.
- Añadir y validar `_csrf` o `X-CSRF-Token`.
- Responder `405` para métodos incorrectos y `419` para CSRF inválido.
- No usar enlaces GET para suspender, cancelar, entregar o cambiar estados.

- [ ] Cocina protegida
- [ ] Superadmin protegido
- [ ] Pruebas CSRF agregadas

### 1.6 Corregir duración y regeneración de sesiones

**Problema**

La sesión está configurada por diez años. Esto es especialmente peligroso en computadoras compartidas.

**Archivos**

- `config/config.php`
- `index.php`
- `app/controllers/AuthController.php`

**Corrección**

- Regenerar ID de sesión después de cada login.
- Definir expiración razonable para Admin/Superadmin.
- Mantener el turno de Caja, pero bloquear la pantalla por inactividad y pedir PIN para volver.
- Invalidar cookies correctamente al cerrar sesión.
- No guardar el hash de contraseña ni más datos de usuario de los necesarios dentro de la sesión.

- [ ] Política de sesión implementada
- [ ] Bloqueo de Caja por inactividad
- [ ] Pruebas de login/logout

## 2. Pedido, pago, Cocina y entrega (P0–P1)

### 2.1 Separar estado operativo y estado financiero

**Estado operativo recomendado**

`pendiente → en_preparacion → listo → entregado`

Ruta de cancelación:

`pendiente/en_preparacion/listo → cancelado`

**Estado financiero recomendado**

`pendiente | parcial | pagado | reembolso_pendiente | reembolsado | fallido`

**Corrección**

- No inferir pago únicamente de `pagado_at`.
- No usar `entregado` como sinónimo de cobrado.
- Registrar cada transición en `rest_pedido_eventos` con usuario, rol, origen, fecha y motivo.

- [ ] Migración creada
- [ ] Modelo de estados centralizado
- [ ] Transiciones inválidas bloqueadas

### 2.2 Enviar las ventas POS a Cocina

**Problema**

`RestCajaController::cobrar()` crea actualmente el pedido con `estado='entregado'`. La venta nunca aparece en Cocina.

**Corrección**

- Agregar `requiere_preparacion` a productos.
- Si algún producto requiere preparación, crear el pedido como `pendiente`.
- Si todos son productos inmediatos, crear el pedido como `listo`.
- Cambiar el texto principal a “Cobrar y enviar a cocina” o “Cobrar y dejar listo”.
- Descontar inventario en un momento único y documentado, no por el simple hecho de cobrar.

**Prueba obligatoria**

Una venta de hot cakes cobrada en Caja aparece inmediatamente en Cocina y no puede entregarse hasta quedar lista.

- [ ] Flujo POS–Cocina corregido
- [ ] Productos inmediatos contemplados
- [ ] Prueba E2E agregada

### 2.3 Impedir entrega antes de que Cocina termine

**Problema**

Caja puede cobrar o entregar pedidos de la app sin exigir `estado='listo'`.

**Corrección**

- Caja puede cobrar un pedido pendiente, pero no entregarlo.
- El botón “Entregar” solo aparece y funciona para pedidos `listo`.
- Un override requiere PIN de Admin, motivo y auditoría.
- Cocina deja de mostrar “Entregar / recogido”; su responsabilidad termina en “Listo”.

- [ ] Validación de servidor
- [ ] UI de Caja actualizada
- [ ] Cocina limitada a preparación

### 2.4 Hacer idempotentes y concurrentes las transiciones

**Corrección**

- Usar actualizaciones condicionadas por estado actual.
- Verificar filas afectadas para detectar carreras.
- Evitar doble pago, doble entrega, doble descuento de inventario y doble cierre.
- Agregar `version` al pedido o bloqueo transaccional cuando corresponda.
- Hacer que los endpoints de estado devuelvan el estado actual cuando la acción ya fue aplicada.

- [ ] Doble clic probado
- [ ] Dos terminales probadas
- [ ] Reintento por timeout probado

## 3. Saneamiento de la base real (P0)

> Ejecutar únicamente después de respaldo completo y mediante una migración revisable. No editar el dump original.

### 3.1 Reconciliar pedidos, pagos y turnos

**Casos a localizar**

- pagos con `turno_caja_id` cuyo pedido no tiene el mismo turno;
- pedidos con cobros, pero sin `pagado_at` o `metodo_pago`;
- pedidos con turno, pero sin pago;
- snapshots de turnos diferentes a la suma de movimientos;
- cortes automáticos duplicados o incoherentes.

**Caso ya comprobado**

El pedido POS de $335 tiene un pago ligado al turno 1, pero el pedido no quedó ligado al turno/cajero. El turno cerró con $335 y cero pedidos vendidos.

**Corrección**

- Crear consulta de auditoría antes/después.
- Backfill de turno, cajero, método y fecha solo cuando exista evidencia inequívoca en `rest_pedido_pagos`.
- Recalcular el snapshot del turno y el espejo del corte.
- Guardar informe de filas modificadas y totales monetarios.

- [ ] Respaldo creado
- [ ] Auditoría previa guardada
- [ ] Migración ejecutada en copia
- [ ] Sumas reconciliadas

### 3.2 Revisar pedidos de efectivo marcados como pagados

**Corrección**

- Localizar pedidos `metodo_pago` efectivo con `pagado_at`, pero sin cobro correspondiente.
- No borrarlos automáticamente sin evidencia; generar listado de revisión.
- Los pedidos confirmados como “pagar al recoger” deben volver a estado financiero pendiente.

- [ ] Listado generado
- [ ] Casos revisados
- [ ] Datos corregidos

### 3.3 Recalcular subtotales de partidas móviles

**Problema comprobado**

Existen partidas de pedidos móviles con `subtotal = 0.00` aunque el pedido tenga total positivo.

**Corrección**

- Recalcular solo cuando precio, cantidad y total permitan una reconstrucción segura.
- Corregir la API para que el servidor siempre persista precio unitario y subtotal calculados.
- Comparar la suma de partidas con el subtotal del pedido.

- [ ] Datos históricos corregidos
- [ ] Validación de suma implementada

### 3.4 Agregar integridad referencial

**Problema**

El dump tiene 71 tablas y solamente 13 claves foráneas. Las tablas centrales de pedidos, partidas, pagos y turnos carecen de varias relaciones.

**Corrección**

- Detectar y resolver huérfanos antes de añadir restricciones.
- Agregar FKs e índices para pedidos, partidas, pagos, turnos, movimientos, recetas e ingredientes.
- Usar `RESTRICT` o borrado lógico para transacciones históricas.
- No aplicar cascadas que puedan borrar ventas o pagos.

- [ ] Huérfanos auditados
- [ ] FKs agregadas
- [ ] Rendimiento de consultas comprobado

## 4. Inventario y recetas (P1)

### 4.1 Corregir recetas incompletas

**Problema comprobado**

Solo dos de seis productos tienen receta y las cantidades existentes son `0.000`. Además, las relaciones de ingredientes de prueba no representan recetas reales.

**Corrección**

- Marcar cada producto como `sin receta`, `receta incompleta` o `receta lista`.
- No permitir activar descuento automático con cantidades cero.
- Validar conversiones entre unidad de compra y unidad de consumo.
- Completar recetas reales antes de usar costos o existencias como datos confiables.

- [ ] Validador de recetas
- [ ] Recetas del catálogo completadas
- [ ] Costos recalculados

### 4.2 Definir un único momento para descontar inventario

**Recomendación**

Descontar al iniciar preparación. Una cancelación posterior debe producir reversa o merma auditada, según lo ocurrido físicamente.

**Corrección**

- Crear una referencia única pedido–partida–movimiento.
- Proteger contra doble descuento.
- Mostrar movimientos y reversas en el detalle del pedido.

- [ ] Regla implementada
- [ ] Idempotencia probada
- [ ] Cancelación probada

## 5. Correcciones UX de Caja (P1)

- [ ] Mostrar por separado estado de preparación y estado de pago.
- [ ] Crear bandejas: Por cobrar, En preparación, Listos para entregar y Entregados recientes.
- [ ] Mantener catálogo + carrito como estructura principal.
- [ ] Reemplazar `alert`, `prompt` y `confirm` por modales accesibles.
- [ ] Mostrar estado de conexión, último refresco y errores recuperables.
- [ ] No perder el carrito ante un fallo temporal.
- [ ] Permitir editar cantidad, modificadores y nota desde la partida.
- [ ] Dar foco a “Nueva venta” después de cobrar.
- [ ] Mostrar devolución y efecto por método antes de confirmar cancelación.
- [ ] Añadir prueba guiada para impresora 58/80 mm.
- [ ] Mantener objetivos táctiles mínimos de 48 px.

## 6. Correcciones UX de Cocina (P1)

- [ ] Tablero con columnas Nuevos, En preparación y Listos.
- [ ] Tipografía y botones grandes para distancia y uso con guantes.
- [ ] Notas, exclusiones y alergias con máxima jerarquía visual.
- [ ] Sonido al recibir un pedido nuevo y control visible de silencio.
- [ ] Actualización incremental sin recargar toda la página.
- [ ] Indicador de conexión y último refresco.
- [ ] Verificar `response.ok` y el resultado JSON antes de cambiar la interfaz.
- [ ] Mantener scroll, foco y acciones en progreso durante actualizaciones.
- [ ] Estaciones configurables: cocina, bebidas, empaque u otras.
- [ ] Historial breve y deshacer controlado del último avance.

## 7. Administración y Superadmin (P2)

### Administración

- [ ] Dashboard orientado a acciones: retrasos, diferencias, reembolsos, stock y recetas incompletas.
- [ ] Gestión de PIN, bloqueo y permisos de staff.
- [ ] Configuración de estaciones y preparación por producto.
- [ ] Conciliación de pagos y reembolsos pendientes.
- [ ] Unificar componentes visuales y retirar estilos inline gradualmente.

### Superadmin

- [ ] Detalle completo de negocio.
- [ ] Planes y comisiones.
- [ ] Soporte y auditoría.
- [ ] Configuración global.
- [ ] Salud de integraciones.
- [ ] Impersonación de Admin con auditoría.
- [ ] Paginación y filtros de servidor.

## 8. Limpieza de legado y documentación (P2)

**Problema**

El código todavía referencia mesas, visitas, reservaciones, tickets y alertas que no existen en el dump marketplace.

**Corrección**

- Definir formalmente si el producto será pickup/delivery o también tendrá servicio en mesa.
- Para el alcance actual, retirar rutas y controladores de reservaciones/mesas no soportados.
- Eliminar o aislar modelos muertos para impedir que una ruta pública llegue a una tabla inexistente.
- Consolidar tablas duplicadas de modificadores.
- Dividir `ApiController.php` por dominios.
- Actualizar README: hoy no coincide con el nombre, alcance ni stack real del producto.
- Unificar gradualmente las referencias de Jungle Pizza, CarniHub, AMARE y CapiRest.

- [ ] Alcance funcional fijado
- [ ] Rutas muertas retiradas
- [ ] README actualizado
- [ ] Mapa de módulos actualizado

## 9. Pruebas obligatorias antes de producción

### Sintaxis y calidad

- [ ] Lint de todos los PHP.
- [ ] Validación sintáctica de todos los JavaScript.
- [ ] Análisis estático de PHP.
- [ ] CI activa para cada commit/PR.

### Pruebas unitarias

- [ ] Precios y modificadores.
- [ ] Descuentos y autorización.
- [ ] Propina e IVA incluido.
- [ ] Pago mixto y cambio.
- [ ] Máquina de estados.
- [ ] Idempotencia.

### Pruebas de integración

- [ ] Pedido POS → pago → Cocina → listo → entrega → inventario → cierre.
- [ ] Pedido app efectivo → preparación → cobro → entrega.
- [ ] Pedido app tarjeta → webhook válido → preparación → entrega.
- [ ] Cancelación antes y después de preparar.
- [ ] Reembolso por cada método.
- [ ] Dos cajeros simultáneos.
- [ ] Aislamiento entre dos restaurantes.
- [ ] Cierre con diferencia y pedidos pendientes.

### Criterio final

El sistema puede salir a producción cuando:

1. no confía en el cliente para precios o pagos;
2. cada cobro se reconcilia con pedido, turno y corte;
3. ningún pedido se entrega antes de estar listo;
4. el inventario se mueve una sola vez;
5. no hay endpoints sensibles sin autenticación, autorización y CSRF aplicables;
6. las pruebas críticas pasan automáticamente;
7. existe respaldo, migración, rollback y monitoreo documentados.

## 10. Orden inmediato de trabajo

1. Corregir detección de columnas.
2. Agregar prueba de persistencia de venta POS.
3. Autenticar pedidos móviles y retirar `body.pagado`.
4. Sanear la venta POS de $335 y revisar pedidos de efectivo.
5. Proteger Cocina y Superadmin con POST + CSRF.
6. Retirar archivos temporales.
7. Implementar estados de pago y operación separados.
8. Conectar correctamente POS → Cocina → Entrega.
9. Corregir recetas e inventario.
10. Aplicar el rediseño UX y completar Admin/Superadmin.
