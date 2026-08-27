# Checklist vivo de implementación — Sistema Restaurante

**Rama de trabajo:** `feature/sistema-completo`  
**Inicio:** 2026-08-26  
**Última actualización:** 2026-08-27
**Estado general:** En progreso

Este archivo se actualiza en cada entrega. Una tarea solo se marca como terminada después de revisar el código y ejecutar las verificaciones aplicables.

## Reglas del trabajo

- [x] Trabajar y publicar únicamente en `feature/sistema-completo`.
- [x] Mantener este checklist actualizado en cada commit.
- [x] Informar qué cambió después de cada entrega.
- [x] Conservar compatibilidad con la aplicación móvil: no eliminar rutas ni cambiar contratos existentes sin una transición versionada.
- [x] Mantener cálculos de precios, descuentos y pagos como autoridad del servidor.
- [x] Usar una interfaz minimalista, accesible y táctil, con tonalidades café y contraste suficiente.
- [ ] Ejecutar prueba integral con la base MySQL antes de declarar producción lista.

## Entrega 1 — Integridad y seguridad crítica

### Seguimiento y documentación

- [x] Analizar código, rama, planes y dump real.
- [x] Crear plan general de mejora.
- [x] Crear documento detallado de correcciones.
- [x] Crear este checklist vivo.

### Persistencia de pedidos POS

- [x] Corregir la detección dinámica de columnas en `RestPedidoModel`.
- [ ] Verificar que un pedido POS guarde estado, turno, cajero, método, fecha de pago y UUID.
- [ ] Verificar idempotencia ante doble clic/reintento.
- [ ] Preparar prueba de integración ejecutable contra MySQL.

### Superficies temporales y mutaciones

- [x] Retirar generador temporal de hashes.
- [x] Retirar endpoints públicos de diagnóstico y pruebas.
- [x] Proteger acciones de Cocina con POST + CSRF.
- [x] Proteger alta y suspensión de Superadmin con POST + CSRF.
- [x] Regenerar el ID de sesión después del login.
- [x] Evitar mostrar errores SQL internos al usuario.

### Compatibilidad móvil

- [ ] Documentar el contrato móvil actual antes de endurecer autenticación.
- [x] Impedir que un pedido en efectivo se marque pagado por una bandera del cliente.
- [ ] Diseñar transición autenticada sin romper versiones instaladas de la app.
- [ ] Mantener respuestas y nombres de campos compatibles durante la transición.

### Verificación de la entrega 1

- [x] Lint de todos los archivos PHP (140 archivos).
- [x] Validación sintáctica de JavaScript (12 archivos).
- [x] `git diff --check` sin errores.
- [x] Checklist actualizado.
- [x] Commit creado: `96ae3cb`.
- [x] Commit publicado en `origin/feature/sistema-completo`.

## Entrega 2 — Flujo correcto Caja → Cocina → Entrega

- [x] Separar estado operativo y estado de pago.
- [x] Crear migración compatible y sin renombrar campos consumidos por móvil.
- [x] Ajustar `004_flujo_caja_cocina.sql` para phpMyAdmin quitando `COMMENT` dentro de SQL dinámico.
- [x] Añadir `requiere_preparacion` a productos.
- [x] Enviar ventas POS a Cocina cuando corresponda.
- [x] Mantener productos inmediatos directamente como listos.
- [x] Impedir entrega antes de `listo` en servidor y UI.
- [x] Quitar la responsabilidad de entrega de la pantalla de Cocina.
- [ ] Crear bandejas de Caja: Por cobrar, En preparación, Listos y Entregados recientes.
- [ ] Registrar eventos y cambios de estado auditables.
- [ ] Probar doble clic, dos terminales y reintento por timeout.
- [x] Actualizar checklist, commit y push.

## Entrega 3 — KDS de Cocina minimalista y accesible

- [ ] Crear diseño de tres columnas: Nuevos, En preparación y Listos.
- [ ] Aplicar paleta café accesible con estados de alto contraste.
- [ ] Aumentar tipografía y objetivos táctiles para uso a distancia/guantes.
- [ ] Resaltar notas, exclusiones y alergias.
- [ ] Añadir sonido configurable para pedidos nuevos.
- [ ] Actualizar incrementalmente sin recargar toda la página.
- [ ] Mostrar conexión, reconexión y última actualización.
- [ ] Mantener scroll y foco durante actualizaciones.
- [ ] Preparar estaciones Cocina/Bebidas/Empaque sin afectar el contrato móvil.
- [ ] Actualizar checklist, commit y push.

## Entrega 4 — Caja minimalista y accesible

- [ ] Conservar catálogo + carrito y reducir ruido visual.
- [ ] Unificar paleta café, espaciado, tipografía y componentes.
- [ ] Mostrar estado de preparación y pago por separado.
- [ ] Reemplazar `alert`, `prompt` y `confirm` por modales accesibles.
- [ ] Mejorar edición de cantidades, notas y modificadores.
- [ ] Conservar carrito ante errores temporales.
- [ ] Añadir estado de red y errores recuperables.
- [ ] Mejorar confirmación de cambio y nueva venta.
- [ ] Revisar contraste, navegación por teclado y lector de pantalla.
- [ ] Actualizar checklist, commit y push.

## Entrega 5 — Crear platillo simplificado (en progreso)

- [x] Reducir la alta inicial a: nombre, categoría, precio y disponibilidad; foto opcional.
- [x] Mover descripción, tiempo, alérgenos y receta a secciones opcionales desplegables.
- [x] Permitir crear una categoría sin salir del formulario ni perder lo capturado.
- [x] Mostrar vista previa sencilla de la foto del producto.
- [x] Explicar en lenguaje simple qué verá Caja, Cocina y el menú.
- [x] Conservar los mismos campos, tablas y rutas para no romper la aplicación móvil.
- [x] Validar precio, imagen, pertenencia al restaurante y campos obligatorios en cliente y servidor.
- [x] Permitir publicar sin receta y explicar cuándo se activa el descuento de inventario.
- [x] Permitir agregar y retirar renglones de receta sin bloquear el alta inicial.
- [x] Proteger alta, edición, disponibilidad, desactivación, importación y categorías con POST + CSRF.
- [x] Añadir navegación por teclado, foco visible, controles táctiles y reducción de movimiento.
- [x] Implementar adaptación responsive para escritorio, tablet y móvil web.
- [ ] Guardar borrador cuando sea seguro hacerlo.
- [ ] Confirmar éxito con accesos directos: “Crear otro” y “Ver menú”.
- [ ] Probar en escritorio, tablet y móvil.
- [x] Ejecutar lint PHP, validación sintáctica JavaScript y `git diff --check`.
- [x] Actualizar checklist, commit y push (`de12b45`).

## Entrega 6 — Inventario y recetas confiables

- [ ] Detectar productos sin receta o con cantidades cero.
- [x] Crear selector simple: sin descuento, receta o producto por unidad.
- [x] Añadir búsqueda y selección rápida de ingredientes mostrando existencias simples.
- [x] Simplificar recetas a selección de ingredientes sin kg, gramos ni unidades visibles.
- [x] Forzar inventario operativo por piezas para ingredientes, bebidas y productos directos.
- [x] Hacer funcional `ingrediente_directo_id`: descontar una unidad por venta con compatibilidad por código.
- [x] Permitir cantidad por venta en productos directos/bebidas con `ingrediente_directo_cantidad`.
- [x] Permitir apagar/activar ingredientes y ocultar automáticamente los platillos afectados.
- [x] Reordenar ingredientes apagados en tarjetas visibles y fáciles de reactivar.
- [x] Preguntar al dejar un ingrediente sin stock si también debe apagarse del menú.
- [x] Bloquear en servidor la venta de platillos con ingredientes apagados.
- [x] Mantener el descuento directo y por receta sin cambiar rutas ni contrato móvil.
- [x] Validar este bloque con lint de 140 archivos PHP, sintaxis JavaScript y `git diff --check`.
- [x] Definir el momento único de descuento de inventario.
- [x] Garantizar movimientos idempotentes.
- [ ] Implementar reversa o merma al cancelar después de preparar.
- [ ] Reconciliar inventario de prueba.
- [x] Actualizar checklist, commit y push (`516639e`).

## Entrega 7 — Administración y Superadmin

- [ ] Dashboard orientado a pendientes y excepciones.
- [ ] Unificar diseño visual y retirar estilos inline gradualmente.
- [ ] Completar gestión de staff, PIN y estaciones.
- [ ] Completar conciliación y reembolsos.
- [ ] Completar planes, comisiones, soporte y auditoría de Superadmin.
- [ ] Añadir paginación y filtros de servidor.
- [ ] Actualizar checklist, commit y push.

## Entrega 8 — Limpieza, pruebas y producción

- [ ] Retirar rutas del modelo anterior que apunten a tablas inexistentes.
- [ ] Actualizar README y documentación de instalación.
- [ ] Unificar nombres de producto sin cambiar integraciones activas.
- [ ] Crear esquema/migraciones reproducibles.
- [ ] Añadir PHPUnit y pruebas de integración.
- [ ] Añadir CI.
- [ ] Ejecutar recorrido E2E completo.
- [ ] Ejecutar pruebas de aislamiento entre restaurantes.
- [ ] Documentar respaldo, rollback y monitoreo.
- [ ] Piloto controlado en Caja y Cocina.
- [ ] Publicación final en la rama.

## Registro de entregas

| Entrega | Estado | Commit | Resumen |
|---|---|---|---|
| Diagnóstico | Publicado | `96ae3cb` | Plan, correcciones y checklist creados. |
| 1. Integridad y seguridad | En progreso | `96ae3cb` | Detección de columnas corregida; diagnósticos retirados; CSRF y sesión reforzados. Falta prueba MySQL y transición de autenticación móvil. |
| 2. Caja–Cocina–Entrega | Publicado, pendiente prueba MySQL | `516639e` | POS y app quedan en cola, Cocina solo prepara, Caja cobra/entrega, productos inmediatos quedan listos y la entrega descuenta inventario una sola vez. |
| 3. Cocina UX | Pendiente | — | — |
| 4. Caja UX | Pendiente | — | — |
| 5. Crear platillo | Publicado, en progreso | `de12b45` | Alta simplificada, receta opcional, categoría en línea, validaciones, CSRF y diseño café accesible; faltan prueba visual y mejoras secundarias. |
| 6. Inventario | Publicado, en progreso | `70a6dac`, `516639e` | Selector de consumo simplificado, vínculo unitario con cantidad por venta, e ingredientes apagables que ocultan platillos afectados; faltan reversas y pruebas integrales con MySQL. |
| 7. Admin/Superadmin | Pendiente | — | — |
| 8. Producción | Pendiente | — | — |
