# UNIVERSIDAD TECNOLÓGICA DE QUERÉTARO

## Nombre Del Proyecto

"SISTEMA INTELIGENTE DE GESTIÓN OPERATIVA PARA RESTAURANTES"

## Nombre De La Organización

IMPACTOS DIGITALES S.A. DE C.V.

Reporte de Estadía que como parte de los requisitos para obtener el título de
TÉCNICO SUPERIOR UNIVERSITARIO EN DESARROLLO DE SOFTWARE MULTIPLATAFORMA, ÁREA TECNOLOGÍAS DE LA INFORMACIÓN

Presenta

JESÚS SAÚL CALTZONTZI ARREDONDO

Matrícula

2024310029

Asesor Académico

Ing. Adriana Y. Contreras Álvarez

Asesor de la Unidad Económica

Ing. Dan Jonathan Raso Ríos

Santiago de Querétaro, Qro.; junio 2026

# Índice

Resumen  
Agradecimientos  
Definición Del Problema  
Antecedentes  
Justificación Del Proyecto  
Objetivos Del Proyecto  
Entregables  
Recursos Necesarios Para Ejecutar El Proyecto  
Cronograma  
Desarrollo Del Proyecto  
Análisis De Los Resultados  
Conclusiones Y Recomendaciones  
Referencias  
Apéndices  

# Resumen

El presente reporte describe el desarrollo de un sistema inteligente de gestión operativa para restaurantes, diseñado para atender problemas relacionados con el registro manual de pedidos, el control limitado de inventarios, la comunicación entre áreas de servicio y cocina, y la falta de información centralizada para la toma de decisiones. La solución se construyó como una plataforma web basada en PHP 8, MySQL/MariaDB, HTML5, CSS, JavaScript y una arquitectura tipo MVC, complementada con servicios externos para pagos, facturación, notificaciones y sincronización con CarniHub.

El sistema permite administrar restaurantes, mesas, menú, recetas, ingredientes, pedidos, tickets, inventario, reservaciones, promociones, finanzas y usuarios operativos. También integra portales diferenciados para administrador, comensal, mesero, cocina, barra, portero y staff, con el objetivo de reducir errores de comunicación y mantener trazabilidad del ciclo completo de atención. El inventario inteligente descuenta ingredientes conforme a las recetas registradas, genera movimientos de stock, calcula proyecciones de consumo y permite generar pedidos sugeridos para reabastecimiento.

Adicionalmente, el proyecto contempla una aplicación móvil desarrollada con React Native y Expo como entregable externo, la cual consume la misma lógica de servicios para extender la experiencia del comensal hacia escenarios móviles. El resultado principal de la estadía fue un prototipo funcional e integrable que documenta la base de datos, los módulos web, las APIs, las integraciones y las evidencias necesarias para su continuidad técnica.

# Agradecimientos

Se agradece a la Universidad Tecnológica de Querétaro por el apoyo institucional brindado durante el desarrollo de la estadía y por proporcionar los lineamientos académicos para la elaboración del presente reporte. También se agradece a IMPACTOS DIGITALES S.A. DE C.V. por facilitar el contexto operativo, la orientación técnica y el acompañamiento necesario para realizar el proyecto.

Asimismo, se reconoce el apoyo del Asesor Académico y del Asesor de la Unidad Económica, quienes dieron seguimiento a las actividades desarrolladas y contribuyeron con observaciones para mejorar la claridad técnica y documental del proyecto. Finalmente, se agradece a la familia, docentes y compañeros que acompañaron el proceso de formación profesional durante esta etapa.

# Definición Del Problema

En el presente capítulo se describe la problemática que dio origen al proyecto, se identifican los antecedentes relevantes del contexto y se expone la justificación que sustenta la realización del sistema. El análisis se desarrolla a partir de la observación de procesos operativos comunes en restaurantes, donde la administración manual de pedidos, inventario y comunicación interna genera errores, retrasos y pérdida de información.

## Introducción

La organización receptora de la estadía es una empresa de desarrollo de software que busca implementar una solución tecnológica para mejorar la operación de restaurantes. La actividad principal del establecimiento considerado como caso de uso consiste en ofrecer servicio de alimentos en modalidad presencial, con interacción entre comensales, meseros, cocina, caja, administración y personal de control de acceso o salida.

En el proceso tradicional, los pedidos pueden registrarse mediante comandas físicas o herramientas aisladas; el inventario suele actualizarse de forma manual y la información de mesas, tickets, reservaciones, promociones y consumo no siempre se encuentra integrada. Esta fragmentación dificulta conocer el estado real de cada pedido, identificar faltantes de ingredientes, calcular necesidades de reabastecimiento y mantener un historial confiable de ventas y movimientos.

La solución propuesta consiste en el desarrollo de una plataforma web que cubra el ciclo operativo del restaurante desde la configuración inicial hasta la gestión de pedidos, inventario y finanzas. La plataforma se apoya en una base de datos relacional, módulos por rol, APIs, servicios externos y un componente de inventario inteligente que descuenta insumos conforme a las recetas utilizadas en cada pedido.

Las competencias del programa educativo aplicadas en el proyecto comprenden diseño e implementación de bases de datos relacionales, programación backend, desarrollo web, consumo e implementación de APIs, diseño de interfaces, validación funcional, integración de servicios y documentación técnica.

## Antecedentes

La industria restaurantera constituye uno de los sectores económicos relevantes en México por su número de unidades económicas, empleos generados y participación en la actividad de servicios. En este tipo de establecimientos, la eficiencia operativa depende de la coordinación entre varias áreas: recepción, mesas, cocina, barra, caja, administración e inventario.

A pesar de la disponibilidad de sistemas punto de venta, muchos negocios pequeños y medianos mantienen procesos parcialmente manuales o usan herramientas separadas para ventas, inventario y reservas. Esta situación genera duplicidad de registros, diferencias entre inventario físico y sistema, poca visibilidad sobre el estado de los pedidos y dificultad para analizar el comportamiento de consumo.

Los sistemas de gestión restaurantera modernos buscan integrar pedidos, recetas, inventario, usuarios, roles, tickets, reservaciones y reportes en una sola plataforma. Sin embargo, una solución de este tipo requiere diseñar una base de datos consistente, definir permisos por rol, mantener estados operativos claros y permitir integraciones con pagos, facturación y notificaciones.

En este contexto surge la necesidad de desarrollar una solución web que no solo registre ventas, sino que también conecte el pedido con la receta, el consumo de ingredientes, la preparación en cocina, la entrega al comensal, el ticket final y la posibilidad de sugerir reabastecimiento.

## Justificación Del Proyecto

La justificación del presente proyecto descansa en tres dimensiones: operativa, tecnológica y administrativa.

Desde el punto de vista operativo, la plataforma permite centralizar información que normalmente se encuentra dispersa. Al separar los accesos por rol, cada usuario visualiza las acciones que le corresponden: el administrador configura el restaurante, el comensal consulta el menú, el mesero da seguimiento a mesas, cocina actualiza la preparación, barra atiende bebidas, portero valida entradas o salidas y el personal administrativo revisa ventas, tickets, finanzas e inventario.

Desde la perspectiva tecnológica, el uso de PHP 8, MySQL/MariaDB y arquitectura MVC facilita el despliegue en entornos comunes de hosting y permite mantener una separación clara entre controladores, modelos, vistas y servicios. La base de datos relacional permite preservar integridad entre restaurantes, mesas, platillos, recetas, ingredientes, pedidos, tickets y movimientos de inventario.

Desde la perspectiva administrativa, el sistema ofrece información útil para la toma de decisiones: stock bajo, movimientos de inventario, pedidos sugeridos, ventas, cortes, gastos, retiros, promociones y clientes. Además, la integración con servicios externos como FacturAPI, PayPal, Stripe, WhatsApp, correo electrónico y CarniHub amplía el alcance del sistema hacia pagos, facturación, comunicación y reabastecimiento.

# Objetivos Del Proyecto

En el presente capítulo se definen el objetivo general y los objetivos específicos del proyecto, los cuales orientan el desarrollo técnico y sirven como criterios para evaluar los resultados obtenidos.

## Objetivo General

Desarrollar e implementar un sistema web de gestión integral para restaurantes, basado en arquitectura MVC, base de datos relacional, módulos por rol e inventario inteligente, que permita administrar pedidos, menú, recetas, ingredientes, mesas, tickets, finanzas, reservaciones, promociones, usuarios operativos e integraciones externas, complementado con una aplicación móvil React Native/Expo que consuma la misma lógica de servicios.

## Objetivos Específicos

1. Diseñar el modelo de base de datos relacional para centralizar información de restaurantes, usuarios, mesas, menú, recetas, ingredientes, pedidos, tickets, inventario, staff, finanzas, reservaciones, promociones e integraciones.
2. Implementar la arquitectura web MVC en PHP 8, separando rutas, controladores, modelos, vistas, servicios y configuración general del sistema.
3. Desarrollar módulos web por rol para administrador, comensal, mesero, cocina, barra, portero y staff, considerando permisos, sesiones y flujos operativos diferenciados.
4. Construir el flujo de pedidos desde el menú público o acceso por mesa, integrando selección de platillos, modificadores, extras, estados de preparación, entrega, ticket y validación de salida.
5. Implementar el inventario inteligente mediante recetas, descuento automático de ingredientes, movimientos de stock, alertas, proyección de consumo y pedidos sugeridos.
6. Integrar servicios externos para pagos, facturación, notificaciones, correo electrónico, API CarniHub y sincronización de información.
7. Documentar la aplicación móvil React Native/Expo como entregable externo que consume los servicios del sistema para ampliar el flujo del comensal.
8. Validar el sistema mediante pruebas funcionales por módulo, revisión de flujos completos y documentación de evidencias técnicas.

# Entregables

El proyecto se organizó en tres fases de entrega para asegurar retroalimentación progresiva por parte del Asesor de la Unidad Económica y permitir ajustes durante el desarrollo. Los entregables se dividen en operables y documentales, conforme a los lineamientos de la guía de estadía UTEQ.

## Fase 1: Análisis, Diseño Y Planeación

Durante la primera fase se definieron los elementos base del proyecto, sus requerimientos funcionales y la estructura técnica inicial.

Entregables operables:

- Estructura inicial del proyecto web con separación de carpetas para controladores, modelos, vistas, servicios, configuración, recursos públicos, migraciones y tareas programadas.
- Diseño inicial de rutas y front controller para atender los diferentes módulos del sistema.
- Configuración base para conexión a MySQL/MariaDB mediante PDO.

Entregables documentales:

- Especificación de requerimientos funcionales y no funcionales.
- Modelo entidad-relación de la base de datos.
- Listado de módulos por rol.
- Planeación de actividades por semana.
- Definición de entregables por fase.
- Identificación de recursos técnicos necesarios.

## Fase 2: Desarrollo Web, Backend, Integraciones Y App Móvil

Durante la segunda fase se implementaron los módulos funcionales del sistema web, la lógica de negocio y las integraciones necesarias.

Entregables operables:

- Sistema web PHP 8 con arquitectura MVC.
- Módulo de autenticación y sesiones diferenciadas por rol.
- Panel administrativo para restaurante, configuración, mesas, menú, inventario, pedidos, finanzas, facturación, clientes, reservaciones y promociones.
- Menú público para comensales con flujo de pedido.
- Portal de mesero para seguimiento de mesas, entregas y alertas.
- Portal de cocina/chef para preparación de pedidos.
- Portal de barra para atención de bebidas.
- Portal de portero para validación de entradas y salidas.
- Módulo de inventario con ingredientes, recetas, movimientos, stock bajo, proyecciones y pedidos sugeridos.
- Integración con servicios de pago, facturación, correo, WhatsApp y API CarniHub.
- Aplicación móvil React Native/Expo como entregable externo que consume la lógica del sistema.

Entregables documentales:

- Documentación de endpoints y rutas.
- Manual técnico de instalación y configuración.
- Evidencia de estructura del repositorio.
- Capturas de pantallas por rol.
- Evidencia de migraciones de base de datos.

## Fase 3: Pruebas, Documentación Y Cierre

Durante la tercera fase se revisaron los flujos desarrollados, se documentaron resultados y se preparó la entrega final.

Entregables operables:

- Sistema web integrado y navegable por módulos.
- Flujos de pedido, cocina, entrega, ticket, inventario y pedidos sugeridos listos para demostración.
- Configuración de servicios externos mediante variables y paneles de administración.

Entregables documentales:

- Reporte de pruebas funcionales.
- Manual de instalación y configuración.
- Colección o listado de endpoints.
- Evidencia de pantallas.
- Reporte final de estadía.
- Apéndices con diagramas, capturas y consultas relevantes.

# Recursos Necesarios Para Ejecutar El Proyecto

Los recursos necesarios se clasifican en humanos, tecnológicos, software e infraestructura.

## Recursos Humanos

- Estudiante desarrollador responsable del análisis, diseño, implementación y documentación.
- Asesor académico encargado del seguimiento metodológico y documental.
- Asesor de la Unidad Económica encargado de orientar el alcance técnico y validar entregables.
- Personal operativo de referencia para comprender roles de restaurante y flujos de atención.

## Recursos Tecnológicos

- Computadora portátil para desarrollo.
- Conexión a internet.
- Servidor local o entorno compatible con PHP 8 y MySQL/MariaDB.
- Repositorio Git para control de versiones.
- Navegador web para pruebas de módulos.
- Dispositivo móvil con Expo Go para revisar la aplicación móvil externa.

## Recursos De Software

- PHP 8 o superior.
- MySQL/MariaDB.
- Apache con mod_rewrite o servidor equivalente.
- Composer para dependencias PHP.
- HTML5, CSS3, JavaScript y Tailwind CSS.
- Librerías de apoyo como Chart.js, jsPDF, jsQR y dependencias de pagos/facturación.
- React Native y Expo para la aplicación móvil externa.
- Herramientas de documentación como Word, Canva o software equivalente para diagramas.

## Infraestructura E Integraciones

- Hosting o VPS compatible con PHP y MySQL.
- Dominio o subdominio de prueba.
- Credenciales para servicios externos cuando aplique: Stripe, PayPal, FacturAPI, WhatsApp, correo y API CarniHub.
- Carpetas de carga para imágenes, evidencias y recursos públicos.

# Cronograma

La planeación de actividades se realizó conforme a las fases del proyecto y se alinea con los subtítulos utilizados en el capítulo Desarrollo del Proyecto. La guía UTEQ indica que los subtítulos del desarrollo deben corresponder con las actividades nombradas en el diagrama de Gantt; por ello se recomienda actualizar la gráfica para usar exactamente los nombres de la siguiente tabla.

| Semanas | Actividad | Entregables Relacionados |
|---|---|---|
| 1 a 2 | Levantamiento De Requerimientos Y Diseño Funcional | Requerimientos, roles, flujos, criterios de aceptación |
| 2 a 4 | Diseño Del Modelo De Base De Datos MySQL | Diagrama ER, tablas por módulo, migraciones |
| 4 a 5 | Implementación De La Arquitectura Web MVC | Front controller, rutas, modelos, vistas, servicios |
| 6 a 8 | Desarrollo De Módulos Web Por Rol | Administrador, comensal, mesero, cocina, barra, portero y staff |
| 8 a 9 | Desarrollo Del Flujo De Pedidos | Menú, modificadores, estados, tickets y validación |
| 9 a 10 | Implementación Del Inventario Inteligente | Recetas, descuento, movimientos, forecast y pedidos sugeridos |
| 10 a 11 | Integración De Servicios Externos | Pagos, facturación, notificaciones, CarniHub y QR |
| 10 a 11 | Desarrollo De La Aplicación Móvil React Native/Expo | Flujo móvil externo conectado a servicios |
| 12 a 13 | Pruebas, Ajustes Y Puesta En Marcha | Pruebas funcionales, evidencias, manuales y reporte final |

# Desarrollo Del Proyecto

En el presente capítulo se describe de manera detallada el desarrollo del sistema inteligente de gestión operativa para restaurantes. La explicación sigue las actividades establecidas en el cronograma, con el propósito de mantener congruencia entre la planeación, los entregables y la construcción técnica del proyecto. Se describen las decisiones tomadas desde el diseño de la base de datos hasta la implementación de módulos web, inventario inteligente, integraciones externas y aplicación móvil.

## Levantamiento De Requerimientos Y Diseño Funcional

La primera actividad consistió en identificar los problemas operativos que debía resolver el sistema. Se analizaron los flujos principales de un restaurante: configuración del establecimiento, administración de mesas, creación del menú, registro de recetas, control de ingredientes, toma de pedidos, preparación en cocina, entrega al comensal, emisión de ticket, control de salida, reservaciones, promociones, seguimiento de clientes y revisión financiera.

Con base en este análisis se definieron los roles principales del sistema:

- Administrador: configura el restaurante, usuarios, menú, inventario, finanzas, facturación y reportes.
- Comensal: consulta el menú público, selecciona platillos, personaliza pedidos y da seguimiento a su consumo.
- Mesero: atiende mesas, reclama zonas, consulta pedidos listos y marca entregas.
- Cocina o chef: visualiza pedidos pendientes, cambia estados de preparación y consulta recetas.
- Barra: atiende productos o pedidos correspondientes a bebidas.
- Portero: registra entradas, salidas y valida códigos QR de salida.
- Staff: accede a funciones operativas específicas de acuerdo con el rol asignado.

También se definieron criterios funcionales: cada pedido debía tener folio, estado, mesa o visita asociada, items, totales y relación con sus modificadores. El inventario debía conectarse con recetas para descontar ingredientes de forma trazable. Las integraciones externas debían permanecer configurables para facilitar el despliegue en diferentes ambientes.

## Diseño Del Modelo De Base De Datos MySQL

La segunda actividad fue el diseño de la base de datos relacional. El repositorio contiene una migración principal de despliegue standalone que define 35 tablas base, además de migraciones incrementales para ampliar pagos, facturación, promociones, modificadores, reservas, ubicación, sincronización y compatibilidad con CarniHub.

El modelo se organizó por familias de información:

| Familia | Tablas Principales | Propósito |
|---|---|---|
| Seguridad y configuración | roles, usuarios, global_settings, action_logs, login_intentos | Control de acceso, parámetros y trazabilidad |
| Restaurante | rest_restaurantes, rest_zonas, rest_mesas | Datos del restaurante, zonas y mesas |
| Menú y recetas | rest_categorias_menu, rest_platillos, rest_recetas, rest_ingredientes, rest_receta_ingredientes | Catálogo, recetas e insumos |
| Pedidos | rest_pedidos, rest_pedido_items, rest_tickets | Registro de comandas, partidas y tickets |
| Modificadores | rest_modificadores, rest_platillo_modificador, rest_pedido_item_modificadores | Extras, exclusiones y personalización |
| Inventario | rest_movimientos_inventario, rest_pedidos_sugeridos, rest_pedido_sugerido_items | Stock, movimientos, forecast y reabastecimiento |
| Clientes y visitas | rest_comensales, rest_visitas | Historial de comensales y visitas |
| Staff | rest_staff, rest_mesero_turno, rest_alertas | Personal operativo, turnos y alertas |
| Finanzas | rest_gastos, rest_retiros, rest_cortes | Control de ingresos, egresos y cortes |
| Reservas y promociones | rest_reservaciones, rest_promociones, rest_promocion_comensales | Reservas, campañas y asignaciones |
| Integraciones | api_tokens, api_access_log, carnihub_api_config | Acceso API y conexión con CarniHub |

El diseño relacional permite que cada platillo tenga una receta, cada receta contenga ingredientes, cada pedido contenga items y cada item pueda incluir modificadores. Esta estructura permite calcular costos, descontar stock y conservar historial de consumo. También facilita que los módulos de cocina, mesero, ticket e inventario trabajen sobre una fuente de datos común.

Las migraciones posteriores amplían el sistema sin reemplazar la estructura principal. Por ejemplo, se agregaron campos para promociones, coordenadas, pagos, tarjetas guardadas, webhook de CarniHub, campos de FacturAPI, compatibilidad de ingredientes y selector unificado de modificadores.

## Implementación De La Arquitectura Web MVC

La tercera actividad fue implementar la arquitectura web. El sistema utiliza un front controller en `index.php`, responsable de interpretar la URL, iniciar la sesión adecuada, aplicar rutas públicas o protegidas y despachar la petición hacia el controlador correspondiente.

La arquitectura se organiza en cuatro capas principales:

- Configuración: archivos de configuración general y conexión a base de datos.
- Controladores: reciben la petición, validan permisos, coordinan modelos y cargan vistas.
- Modelos: encapsulan consultas SQL y reglas de persistencia.
- Vistas: presentan la interfaz HTML para cada módulo o rol.
- Servicios: concentran integraciones externas y lógica especializada.

El enrutador define rutas como `restaurante`, `rest-menu`, `rest-inventario`, `rest-pedido`, `rest-finanzas`, `rest-factura`, `rest-reserva`, `rest-promocion`, `rest-ticket`, `rest-mesero`, `rest-chef`, `rest-bar`, `rest-portero`, `menu`, `acceso`, `carnihub` y `api`. Esta organización permite que cada módulo tenga un controlador especializado y mantenga responsabilidades separadas.

Un elemento importante de la implementación fue el manejo de sesiones diferenciadas por rol. El sistema usa nombres de cookie con sufijos para que el administrador, chef, mesero, portero, barra y comensal puedan mantener sesiones independientes en un mismo navegador. Esta decisión resuelve un problema común en pruebas operativas, donde diferentes roles pueden necesitar acceder desde el mismo equipo.

## Desarrollo De Módulos Web Por Rol

La cuarta actividad fue construir los módulos web. El sistema no se limita a un solo panel administrativo, sino que separa la experiencia según el usuario operativo.

El módulo administrador permite seleccionar restaurante, editar configuración, gestionar mesas, menú, inventario, pedidos, clientes, promociones, reservaciones, facturas, tickets, finanzas, staff y códigos QR. Este módulo funciona como centro de control del restaurante.

El módulo comensal se implementa mediante rutas públicas de menú. Permite acceder al restaurante, consultar platillos disponibles, personalizar pedidos con exclusiones o extras, confirmar consumo, pagar cuando el flujo lo requiere y consultar pantallas de confirmación o agradecimiento.

El módulo mesero permite consultar mesas, reclamar zonas, revisar pedidos listos, marcar entregas, atender alertas y consultar reservaciones del día. Su propósito es reducir la comunicación manual entre cocina y servicio.

El módulo cocina o chef muestra pedidos e items pendientes, permite marcar preparación, marcar listo y consultar armado o recetas cuando el platillo lo requiere. Esto convierte la pantalla en un sistema tipo KDS orientado a producción.

El módulo barra atiende el flujo operativo de bebidas o partidas asignadas a esa estación. El módulo portero se utiliza para verificar accesos, registrar entradas y salidas, y validar salidas mediante códigos QR.

La separación por rol contribuye a que cada usuario visualice información relevante y evita que funciones administrativas queden expuestas a perfiles operativos.

## Desarrollo Del Flujo De Pedidos

La quinta actividad fue desarrollar el flujo de pedidos. Este proceso comienza con el menú público o con el acceso asociado a mesa o visita. El comensal selecciona platillos, cantidades y modificadores. Los modificadores permiten manejar exclusiones, extras y opciones asociadas a ingredientes del restaurante.

Cuando se crea un pedido, el modelo valida que cada platillo pertenezca al restaurante correspondiente. También valida que los modificadores sean válidos para el platillo y que no excedan el máximo permitido. El sistema calcula el subtotal de cada item considerando el precio base del platillo más los extras seleccionados. Las exclusiones se registran para que cocina e inventario conozcan qué ingrediente no debe considerarse.

El pedido se almacena con folio, mesa, visita, mesero, notas, subtotal y total. Cada partida se guarda en `rest_pedido_items` y sus modificadores se registran en `rest_pedido_item_modificadores`. Esta separación permite consultar el detalle del ticket, mostrar estados a cocina y conservar trazabilidad de personalizaciones.

El flujo continúa con los cambios de estado: pedido creado, preparación, listo, entregado o cancelado, según corresponda. Cocina y mesero actualizan el avance desde sus propios portales. Posteriormente, el sistema permite generar tickets y validar la salida del comensal mediante QR cuando el flujo operativo lo requiere.

## Implementación Del Inventario Inteligente

La sexta actividad fue implementar el inventario inteligente. Este módulo conecta los platillos con recetas e ingredientes, de modo que el consumo real pueda reflejarse en el stock disponible. El modelo de inventario permite registrar entradas, salidas, ajustes y movimientos asociados a referencias específicas.

Cuando un pedido se confirma o alcanza el estado operativo definido, el sistema descuenta ingredientes de acuerdo con la receta del platillo y la cantidad solicitada. Para evitar duplicidades, el descuento utiliza referencias asociadas al pedido o al item. Si ya existe un movimiento para la misma referencia, el sistema no repite el descuento. Esta regla de idempotencia es importante para evitar errores cuando una acción se ejecuta más de una vez.

El sistema también convierte unidades entre gramos, kilogramos, mililitros y litros cuando la receta y el inventario usan unidades compatibles. Además, toma en cuenta exclusiones: si el comensal solicitó omitir un ingrediente, el descuento puede evitar registrar salida de ese insumo.

La proyección de inventario se implementó mediante un servicio de forecast. Este servicio calcula consumo total, consumo promedio diario, promedio móvil, días restantes de stock, cantidad sugerida a pedir y nivel de alerta. Con estos datos se identifican ingredientes críticos, ingredientes en advertencia y productos sin datos suficientes. Cuando un ingrediente está vinculado a CarniHub, el sistema puede agrupar necesidades por proveedor y convertir pedidos sugeridos en órdenes.

## Integración De Servicios Externos

La séptima actividad fue integrar servicios externos para ampliar la funcionalidad del sistema. Las integraciones se aislaron en servicios especializados para facilitar mantenimiento y configuración.

Las integraciones principales son:

- FacturAPI: utilizada para solicitudes de facturación y timbrado cuando la configuración lo permite.
- PayPal y Stripe: utilizados para flujos de pago y configuración de métodos disponibles.
- WhatsApp y correo electrónico: utilizados para notificaciones y comunicación con usuarios o clientes.
- CarniHub API: utilizada para sincronizar información, consultar proveedores, enviar pedidos sugeridos o recibir webhooks.
- QR: utilizado para acceso de comensales, identificación de mesas y validación operativa de salida.

El sistema permite configurar claves y parámetros mediante variables de entorno o tablas de configuración. Esta decisión evita modificar código fuente para cambiar credenciales o activar servicios, lo cual mejora la mantenibilidad y reduce riesgos durante despliegues.

## Desarrollo De La Aplicación Móvil React Native/Expo

La octava actividad corresponde a la aplicación móvil desarrollada con React Native y Expo. Este entregable se documenta como componente externo al repositorio web revisado, pero forma parte del alcance funcional del proyecto. Su propósito es extender la experiencia del comensal hacia dispositivos móviles, reutilizando la lógica y datos disponibles en el backend.

La aplicación móvil contempla flujos de consulta de menú, creación de pedidos, seguimiento de estado y modalidades complementarias como entrega a domicilio o recolección, cuando la configuración del restaurante lo permite. Al consumir servicios compartidos, la información se mantiene consistente con la plataforma web.

Para completar la evidencia de este entregable se recomienda anexar capturas de Expo Go, pantallas principales, estructura del proyecto móvil, archivo de configuración, rutas consumidas y pruebas de conexión con el backend.

## Pruebas, Ajustes Y Puesta En Marcha

La última actividad consistió en revisar el funcionamiento de los módulos y preparar la documentación de cierre. Las pruebas se enfocaron en validar que los flujos principales pudieran ejecutarse de forma integrada:

- Inicio de sesión y control de acceso por rol.
- Configuración de restaurante.
- Registro de mesas y zonas.
- Captura de ingredientes, recetas y platillos.
- Creación de pedidos desde menú público.
- Personalización de platillos mediante modificadores.
- Consulta de pedidos en cocina.
- Marcado de preparación, listo y entregado.
- Generación de ticket.
- Descuento de inventario.
- Revisión de movimientos y alertas.
- Generación de pedidos sugeridos.
- Configuración de pagos y facturación.
- Validación de rutas públicas, API y webhook.

Durante esta etapa también se revisó la congruencia entre el documento, el repositorio y los entregables. Se identificó que la redacción original del reporte mencionaba un modelo de 13 tablas y una descripción general de APIs, pero el desarrollo real del repositorio contiene una estructura más amplia. Por ello se actualizó la documentación para reflejar las 35 tablas base, los módulos reales, las migraciones incrementales y las integraciones implementadas.

# Análisis De Los Resultados

En este capítulo se compara el cumplimiento de los objetivos planteados con los resultados obtenidos. La evaluación se realiza con base en la evidencia disponible en el repositorio, la estructura de base de datos, los módulos implementados y la documentación técnica.

| Objetivo | Resultado Obtenido | Evidencia |
|---|---|---|
| Diseñar base de datos relacional | Cumplido. Se implementó una migración principal con 35 tablas base y migraciones incrementales. | Carpeta `migrations`, especialmente `047_capirest_standalone.sql` |
| Implementar arquitectura MVC en PHP | Cumplido. El sistema separa front controller, controladores, modelos, vistas y servicios. | `index.php`, `app/controllers`, `app/models`, `app/views`, `app/services` |
| Desarrollar módulos web por rol | Cumplido. Existen rutas y controladores para administrador, comensal, mesero, chef, barra, portero y staff. | Mapa de rutas en `index.php` y controladores `Rest*Controller` |
| Construir flujo de pedidos | Cumplido. El sistema crea pedidos, items, modificadores, estados y tickets. | `RestPedidoController`, `RestPedidoModel`, vistas de pedidos y menú público |
| Implementar inventario inteligente | Cumplido. Existen recetas, descuento por orden, movimientos, forecast y pedidos sugeridos. | `RestInventarioModel`, `RestForecastService`, `RestPedidoSugeridoModel` |
| Integrar servicios externos | Cumplido parcialmente según credenciales disponibles. La lógica existe y depende de configuración externa. | Servicios FacturAPI, PayPal, WhatsApp, Email, CarniHub |
| Documentar app móvil React Native/Expo | Cumplido como entregable externo. Requiere anexar evidencia independiente del repo web. | Capturas, proyecto móvil y pruebas Expo Go por anexar |
| Validar flujos funcionales | Cumplido de forma funcional. No se registran métricas cuantitativas en el repo, por lo que se reporta evidencia por flujo. | Capturas, pruebas manuales, checklist y evidencias por anexar |

Los resultados muestran que el objetivo general se cumplió en su componente principal: el sistema web integra módulos operativos, base de datos, flujo de pedidos, inventario y servicios externos. La principal limitación documental es que algunas evidencias, como capturas de la app móvil, pruebas en Expo Go, resultados cuantitativos o colección Postman, deben anexarse como apéndices para fortalecer la trazabilidad del reporte.

También se identificó una diferencia entre la planeación inicial y el resultado técnico final. La primera versión del reporte describía una solución más compacta, mientras que el repositorio evolucionó hacia un sistema con más módulos, más tablas y más integraciones. Esta diferencia no representa un incumplimiento, sino una ampliación del alcance técnico que debe quedar explicada en el reporte para evitar inconsistencias.

# Conclusiones Y Recomendaciones

El desarrollo del sistema permitió aplicar competencias relacionadas con análisis de requerimientos, diseño de bases de datos, programación backend, desarrollo frontend, arquitectura MVC, integración de servicios y documentación técnica. La experiencia permitió comprender que un sistema restaurantero no debe limitarse a registrar ventas, sino que debe conectar pedidos, cocina, inventario, tickets, usuarios, finanzas y datos de operación.

La estrategia de solución condujo al resultado esperado en el componente web. El uso de PHP 8, MySQL/MariaDB y una arquitectura MVC permitió desarrollar un sistema modular, desplegable en entornos de hosting comunes y entendible para futuros mantenedores. La separación por roles favorece la operación diaria y reduce el riesgo de que usuarios accedan a funciones que no les corresponden.

Uno de los aprendizajes principales fue la importancia de diseñar la base de datos antes de construir la interfaz. La relación entre platillos, recetas, ingredientes y pedidos fue fundamental para implementar el inventario inteligente. Sin esa estructura, el descuento automático de stock y la generación de pedidos sugeridos no habrían sido viables.

El impacto esperado para la organización es contar con una plataforma base que puede adaptarse a restaurantes reales, conectar servicios externos y evolucionar hacia una operación más automatizada. El proyecto también deja una estructura técnica reutilizable para nuevas versiones o clientes.

Como recomendaciones, se propone:

- Completar una batería formal de pruebas con evidencias fechadas.
- Anexar capturas por rol y capturas de la aplicación móvil.
- Documentar la colección de endpoints con ejemplos de request y response.
- Agregar pruebas automatizadas para modelos críticos como pedidos e inventario.
- Revisar seguridad antes de producción, especialmente credenciales, tokens, sesiones, permisos y webhooks.
- Generar un manual de usuario separado para administrador, mesero, cocina y portero.
- Mantener las migraciones numeradas y documentar qué versión de base de datos requiere cada despliegue.

# Referencias

Cámara Nacional de la Industria de Restaurantes y Alimentos Condimentados. (2024). Información estadística de la industria restaurantera en México. CANIRAC.

Data México. (2024). Servicios de preparación de alimentos y bebidas. Secretaría de Economía. https://www.economia.gob.mx/datamexico/

Expo. (2026). Expo documentation. https://docs.expo.dev/

FacturAPI. (2026). FacturAPI documentation. https://docs.facturapi.io/

Instituto Nacional de Estadística y Geografía. (2021). Censos económicos y Directorio Estadístico Nacional de Unidades Económicas. INEGI. https://www.inegi.org.mx/

Meta Platforms, Inc. (2026). React Native documentation. https://reactnative.dev/docs/getting-started

Oracle. (2026). MySQL documentation. https://dev.mysql.com/doc/

PayPal. (2026). PayPal developer documentation. https://developer.paypal.com/docs/

PHP Group. (2026). PHP manual. https://www.php.net/manual/

Stripe. (2026). Stripe API reference. https://docs.stripe.com/api

Universidad Tecnológica de Querétaro. (2026). Guía para la redacción del reporte de Estadía UTEQ. Anexo I - EA-P-26 Rev. 00.

# Apéndices

## Apéndice A. Diagrama Entidad-Relación

Agregar imagen del diagrama ER actualizado con las familias de tablas: seguridad, restaurante, menú, recetas, pedidos, inventario, finanzas, reservas, promociones e integraciones.

## Apéndice B. Diagrama De Gantt

Agregar el diagrama de Gantt actualizado con los mismos nombres de actividades usados en el capítulo Desarrollo Del Proyecto.

## Apéndice C. Evidencia De Estructura Del Repositorio

Agregar captura o listado de carpetas principales:

- `app/controllers`
- `app/models`
- `app/views`
- `app/services`
- `config`
- `migrations`
- `public`
- `cron`
- `api`

## Apéndice D. Evidencias De Módulos Web

Agregar capturas de:

- Panel administrador.
- Configuración del restaurante.
- Gestión de menú.
- Gestión de inventario.
- Pedido desde menú público.
- Pantalla de cocina.
- Portal de mesero.
- Portal de portero.
- Tickets y facturación.

## Apéndice E. Evidencias De Base De Datos

Agregar capturas de migraciones aplicadas, tablas creadas y ejemplos de registros de pedidos, items, ingredientes, recetas y movimientos de inventario.

## Apéndice F. Evidencia De API E Integraciones

Agregar capturas o colección de endpoints para:

- Autenticación.
- Pedidos.
- Menú.
- Configuración.
- Webhook CarniHub.
- Facturación.
- Pagos.
- Notificaciones.

## Apéndice G. Evidencia De Aplicación Móvil React Native/Expo

Agregar capturas del proyecto móvil, ejecución en Expo Go, pantallas principales y consumo de endpoints compartidos con la plataforma web.

## Apéndice H. Checklist De Pruebas Funcionales

| Flujo | Estado | Evidencia |
|---|---|---|
| Login por rol | Por anexar | Captura o video |
| Configuración de restaurante | Por anexar | Captura |
| Alta de mesa | Por anexar | Captura |
| Alta de ingrediente | Por anexar | Captura |
| Alta de platillo con receta | Por anexar | Captura |
| Pedido desde menú público | Por anexar | Captura |
| Pedido en cocina | Por anexar | Captura |
| Entrega por mesero | Por anexar | Captura |
| Descuento de inventario | Por anexar | Captura o consulta SQL |
| Pedido sugerido | Por anexar | Captura |
| Facturación o pago | Por anexar | Captura |
| App móvil Expo | Por anexar | Captura |
