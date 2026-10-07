# Guía de uso de MediTrack

<!--
  Esto es lo que el chatbot sabe del sistema. Si cambias una pantalla o agregas
  un módulo, actualiza aquí. No escribas datos reales de pacientes.
-->

## Generalidades
- MediTrack es un ERP para clínicas privadas. Cada usuario tiene un **rol** y el rol define qué módulos puede ver, crear, editar o eliminar.
- Menú lateral:
  - **Dashboard**
  - **Gestión Médica**: Pacientes, Citas, Consultas, Consultorios
  - **Farmacia y Finanzas**: Terminal POS, Caja Central, Despacho Farmacia, Inventario, Proveedores, Recetas, Facturación
  - **Administración**: Personal, Roles y Permisos, Reportes, Configuración, Bitácora
- Para ir rápido a un módulo usa el buscador **"Ir a un módulo…"** arriba del menú o presiona **Ctrl + K**.
- La **campana** de la parte superior muestra las notificaciones.
- Los datos de captura se guardan en **MAYÚSCULAS**.
- Si aparece "No tienes permisos para acceder a este recurso", el rol del usuario no tiene permiso en ese módulo: hay que pedirlo al administrador.

## Módulos que todavía NO están disponibles
- **Despacho Farmacia**: aparece como "Pronto".
- **Facturación** y **Reportes**: aparecen en el menú pero aún no están habilitados.
Si preguntan por ellos, indica que estarán disponibles en una próxima versión.

## Dashboard (Panel general)
- Tarjetas: pacientes de hoy, citas por atender, consultas atendidas del mes y ocupación de consultorios.
- Gráfica de citas por mes (últimos 6 meses): atendidas, programadas y canceladas.
- **Acciones rápidas**: Nueva cita, Registrar paciente, Ver inventario.

## Cuenta y contraseña
- **Cambiar mi correo o contraseña**: ve a **Configuración → Seguridad de la Cuenta** (sección "Credenciales y Acceso de Usuario"). Escribe tu **contraseña actual** y, si quieres cambiarla, la **nueva contraseña** (mínimo 8 caracteres) y su confirmación. Da clic en **Actualizar Credenciales**. Puedes cambiar solo el correo, solo la contraseña o ambos.
- Para entrar a Configuración tu rol necesita permiso en ese módulo; si no lo tienes, pide al administrador que haga el cambio.
- **Olvidé mi contraseña**: el sistema NO tiene opción de recuperación por correo. Hay que pedir al administrador que asigne una nueva.
- **Cerrar sesión**: menú de tu nombre (esquina superior derecha) → Cerrar sesión.

## Pacientes
- **Registrar un paciente**: módulo Pacientes → botón para registrar (o "Registrar paciente" en el Dashboard). Se abre "Registrar Expediente Clínico":
  1. Captura Nombre(s), Apellido Paterno (obligatorios), Apellido Materno y datos de identificación (fecha de nacimiento, género, estado civil, nacionalidad, CURP, grupo sanguíneo).
  2. Datos de contacto (teléfono y celular de 10 dígitos, correo, domicilio) y contacto de emergencia.
  3. Antecedentes (alergias, condiciones, medicamentos).
  4. Lee el **Aviso de Privacidad** y da clic en "Entendido y Aceptar".
  5. Da clic en **Guardar Paciente**.
- **Buscar / editar**: en la lista de "Pacientes registrados" usa el buscador y los filtros; desde Acciones puedes ver, editar ("Actualizar Paciente") o eliminar.
- Eliminar un paciente no se puede deshacer.

## Citas
- Pantalla con **calendario de citas** (agenda médica) y lista de **próximas citas**. Se puede filtrar por doctor.
- **Agendar**: botón **"Agendar nueva cita"** y sigue los pasos:
  1. Paciente y médico (quién asiste y quién atiende) y consultorio.
  2. Fecha y horario.
  3. Tipo de consulta (Primera Vez, Seguimiento o Urgencia), duración y estado.
- Puedes mover una cita arrastrándola en el calendario para cambiar fecha y hora.
- **Estados**: Pendiente, Confirmada, En curso, Finalizada, Cancelada.
- Si el paciente no asistirá, es mejor cambiar la cita a **Cancelada** en lugar de eliminarla, para conservar el historial.

## Consultas
- Muestra la agenda del día ("Tu día de un vistazo"), las próximas citas y el siguiente paciente. Se puede filtrar por médico.
- Para atender, selecciona la cita y da clic en **Atender**: la consulta queda "en curso" y se registra motivo, exploración física, signos vitales y diagnóstico.
- Al finalizar, la consulta queda en **solo lectura**: ya no se puede editar.
- Desde la consulta se puede generar la receta.

## Recetas
- Módulo Recetas: historial de recetas emitidas.
- **Nueva Receta**: flujo guiado de 3 pasos (paciente y signos vitales, medicamentos con dosis y frecuencia/duración, indicaciones generales). También se puede crear desde una consulta.
- Cada receta se puede ver e imprimir en PDF.

## Consultorios
- Directorio de consultorios y **plano de planta** por piso.
- **Registrar consultorio**: nombre, piso y estado. También se puede agregar un piso nuevo.
- Estados: Disponible, Ocupado, Mantenimiento.

## Inventario
- Catálogo de medicamentos con existencias (piezas disponibles), categoría y precios.
- **Registrar nuevo medicamento**: código de barras (EAN-13), nombre y presentación, categoría, precios de compra y venta, stock inicial y **stock mínimo (alerta)**.
- Un medicamento se marca como **stock bajo** cuando sus piezas disponibles llegan al stock mínimo, y **agotado** cuando llegan a 0.
- **Surtir** (un medicamento): suma piezas a sus existencias.
- **Surtir lote de medicamentos**: para registrar varios medicamentos que llegaron en la misma factura; puedes escanear los códigos (si escaneas el mismo dos veces se suma la cantidad).
- **Categorías**: se crean y eliminan desde el mismo módulo.

## Proveedores
- Directorio de proveedores: razón social, RFC, contacto, categoría, condiciones (contado o crédito) y datos bancarios. Botón **Nuevo Proveedor**.

## Terminal POS (punto de venta)
- Busca o escanea el medicamento, agrégalo al carrito y elige el paciente (opcional; si no, es "Público general").
- Al generar la venta se crea un **ticket** que aparta los productos por **25 minutos**; si no se cobra en ese tiempo, se libera.
- El cobro del ticket se hace en **Caja Central**.

## Caja Central
- Muestra los **cobros pendientes** (tickets del POS) y los movimientos del día.
- Para cobrar: selecciona el ticket, elige el método de pago (**efectivo, tarjeta o transferencia**) y procesa el cobro.
- También permite **abrir y cerrar la caja**, registrar movimientos (ingresos y egresos) y descargar el **corte de caja** en PDF.

## Personal y nómina
- **Personal**: miembros del equipo con datos personales, cargo/rol, cédula profesional y, si se desea, **acceso al sistema** (correo y contraseña de 8 caracteres).
- **Nómina** (dentro de Personal): se crean periodos de pago, se agregan empleados y cada recibo incluye días pagados, bonos, horas extra, deducciones e IMSS. Cada empleado se paga por separado y el recibo se puede imprimir en PDF.

## Roles y Permisos
- Crear un cargo/rol y **asignar permisos** por módulo (ver, crear, editar, eliminar).

## Configuración
- **Perfil de la Clínica**: nombre, RUT/identificación, teléfono, correo y dirección.
- **Preferencias y Sistema**: notificaciones e historial, y el texto del **Aviso de Privacidad** que ven los pacientes.
- **Seguridad de la Cuenta**: cambiar tu correo y contraseña.

## Bitácora
- Historial de las acciones realizadas en el sistema (quién hizo qué y cuándo).
