MISALUD - CUENTAS FICTICIAS DE PRUEBA (SOLO LOCAL)

IMPORTANTE: Este proyecto contiene credenciales públicas de DEMOSTRACIÓN. No publicar en internet.
Importa database/misalud.sql usando phpMyAdmin o instalar.php, una sola vez.
Si ya hay usuarios, no se borran ni se cambian contraseñas de cuentas existentes.

CONTRASEÑA COMÚN DE TODAS LAS CUENTAS DEMO:
MiSalud#Demo2026!

ADMINISTRADOR
Acceso: http://localhost/MiSalud/admin/login.php
Correo: admin@misalud.test
Contraseña: MiSalud#Demo2026!

PACIENTES (http://localhost/MiSalud/login.php?rol=paciente)
Ana López Martínez - CURP: DEMO900101HDFPRN01
Luis García Pérez - CURP: PRUE920305MDFBTS02
Sofía Ramírez Torres - CURP: TEST990615HDFRMR03

MEDICOS (http://localhost/MiSalud/login.php?rol=medico)
Elena Ruiz Morales - Cedula: 81000001 - Medicina general
Carlos Vega Santos - Cedula: 81000002 - Dermatología
Mariana Ortiz Cruz - Cedula: 81000003 - Pediatría

Los tres medicos tienen horarios L-V 09:00-14:00 y consultorios 101-103.
Hay tres citas ficticias agendadas para el proximo lunes.
El campo verificado=1 se usa EXCLUSIVAMENTE en estos registros de prueba para ejercitar el flujo de reserva; NO representa una verificacion real de cedulas profesionales.

Para sistemas reales elimina estas cuentas demo, cambia las contraseñas y verifica oficialmente los documentos profesionales antes de activar a los medicos.
Si ya tienes un admin con ese correo, el instalador NO cambia su contraseña.
Después de instalar elimina instalar.php y setup_admin.php por seguridad.
