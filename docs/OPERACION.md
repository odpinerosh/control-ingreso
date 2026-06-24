# OPERACION.md — Sistema Control de Ingreso

Guía completa para instalar, configurar y mantener el sistema.

---

## 1. Requisitos del servidor

- PHP 7.4 o superior (con PDO y extensión `pdo_mysql`)
- MySQL 5.7 o MariaDB 10.3 o superior
- Apache con `mod_authz_core` (disponible en cPanel/LiteSpeed vía `.htaccess`)
- Git instalado en el servidor (para despliegues)

---

## 2. Instalación inicial

### 2.1 Clonar el repositorio

Conectarse por SSH al hosting y ejecutar:

```bash
cd /home/solucio1/
git clone https://github.com/TU_ORG/control-ingreso.git control-ingreso
```

O si ya existe la carpeta:

```bash
cd /home/solucio1/control-ingreso
git pull origin main
```

### 2.2 Crear la base de datos

En cPanel → MySQL Databases:
1. Crear base de datos: `solucio1_control` (el prefijo `solucio1_` lo agrega cPanel automáticamente)
2. Crear usuario MySQL con contraseña segura
3. Asignar **todos los privilegios** del usuario a la base de datos

Luego importar el schema:

```bash
mysql -u solucio1_usuario -p solucio1_control < docs/schema.sql
```

O en cPanel → phpMyAdmin → importar `docs/schema.sql`.

---

## 3. Crear config.php

Este archivo **no está en el repositorio**. Crearlo manualmente:

```bash
cp config.ejemplo.php config.php
nano config.php   # o usar el editor de cPanel
```

Completar los valores reales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'solucio1_control');
define('DB_USER', 'solucio1_usuario');
define('DB_PASS', 'contraseña_real');

$ALLOW_IPS = [
    '186.84.155.242',
    '186.84.155.244',
];
```

---

## 4. Agregar una IP permitida

Cuando una nueva sede salga por una IP pública diferente:

### 4.1 Identificar la IP

Desde esa sede abrir en el navegador:

```
https://solucionescooptraiss.com/control-ingreso/ip.php
```

Anotar el valor de `REMOTE_ADDR`.

### 4.2 Agregar al .htaccess

```apache
<RequireAll>
    Require all denied
    Require ip 186.84.155.242
    Require ip 186.84.155.244
    Require ip NUEVA.IP.AQUI        ← agregar aquí
</RequireAll>
```

### 4.3 Agregar a config.php

```php
$ALLOW_IPS = [
    '186.84.155.242',
    '186.84.155.244',
    'NUEVA.IP.AQUI',   ← agregar aquí
];
```

> **Importante:** ambos archivos deben estar sincronizados. El `.htaccess` bloquea la petición antes de que llegue a PHP. El `config.php` es una segunda capa de seguridad.

---

## 5. Crear token para una sede

### 5.1 Definir el token plano

Convención recomendada: `CODIGO-aleatoriohex`

Ejemplo para Boyacá:
```
BOYACA-9f4c2a1b3d
```

El token debe ser secreto. No publicarlo.

### 5.2 Calcular el SHA-256

**Opción A — Linux/Mac terminal:**

```bash
echo -n "BOYACA-9f4c2a1b3d" | sha256sum
```

Resultado:
```
846c13a5c8e361b5c9cf388ba7a329360d08db7879f8107288b4aeb443413faf
```

**Opción B — PHP:**

```php
echo hash('sha256', 'BOYACA-9f4c2a1b3d');
```

**Opción C — PowerShell (Windows):**

```powershell
$str = [System.Text.Encoding]::UTF8.GetBytes("BOYACA-9f4c2a1b3d")
$sha256 = [System.Security.Cryptography.SHA256]::Create()
[BitConverter]::ToString($sha256.ComputeHash($str)).Replace("-","").ToLower()
```

> **⚠ Importante:** usar `echo -n` (sin salto de línea al final). El hash debe tener exactamente 64 caracteres hex.

---

## 6. Insertar una sede en la base de datos

```sql
INSERT INTO sedes (codigo, nombre, token_hash, activa)
VALUES (
    'BOYACA',
    'Boyacá',
    '846c13a5c8e361b5c9cf388ba7a329360d08db7879f8107288b4aeb443413faf',
    1
);
```

Verificar con:

```sql
SELECT * FROM sedes;
```

---

## 7. Construir la URL de acceso de cada sede

Formato:

```
https://solucionescooptraiss.com/control-ingreso/?sede=BOYACA&k=BOYACA-9f4c2a1b3d
```

- `sede` = código exacto que está en la columna `codigo` de la tabla `sedes`
- `k` = token **plano** (no el hash). El hash solo se guarda en la BD.

---

## 8. Crear acceso directo en Windows (por sede)

1. En la computadora de la sede, abrir Chrome o Edge.
2. Navegar a la URL completa de la sede (con `?sede=...&k=...`).
3. Menú (⋮) → **Más herramientas** → **Crear acceso directo**.
4. Marcar ✅ **Abrir como ventana**.
5. Clic en **Crear**.

El acceso directo aparecerá en el escritorio. Al abrirlo, el sistema se muestra como aplicación independiente sin barra de URL, ideal para uso como quiosco.

---

## 9. Despliegue con Git

Cada vez que haya cambios en el código:

```bash
cd /home/solucio1/control-ingreso
git pull origin main
```

> `config.php` nunca se sobreescribe porque está en `.gitignore` y no está en el repositorio.

---

## 10. Diagnóstico: error "Prohibido" / 403

Si al abrir el sistema aparece **"Prohibido"** o **"403 Forbidden"**:

1. Abrir `ip.php` desde esa misma red (puede que el navegador lo muestre si solo hay bloqueo en Apache y no en PHP):
   ```
   https://solucionescooptraiss.com/control-ingreso/ip.php
   ```
2. Anotar la IP que aparece en `REMOTE_ADDR`.
3. Agregar esa IP en `.htaccess` y en `config.php` → `$ALLOW_IPS` (ver sección 4).
4. Si `ip.php` también da 403, la IP no está autorizada ni siquiera para ese archivo. En ese caso, conectarse por SSH y ejecutar:
   ```bash
   echo $SSH_CLIENT   # muestra la IP de la conexión SSH
   ```
   O consultar al proveedor de internet de la sede.

---

## 11. Verificar marcaciones registradas

```sql
SELECT * FROM marcaciones ORDER BY fecha_hora DESC LIMIT 50;
```

Filtrar por sede:

```sql
SELECT * FROM marcaciones
WHERE sede = 'BOYACA'
  AND DATE(fecha_hora) = CURDATE()
ORDER BY cedula, fecha_hora;
```

---

## 12. Activar / desactivar una sede

```sql
-- Desactivar
UPDATE sedes SET activa = 0 WHERE codigo = 'BOYACA';

-- Reactivar
UPDATE sedes SET activa = 1 WHERE codigo = 'BOYACA';
```

---

## Estructura de archivos del proyecto

```
control-ingreso/
├── index.php            ← UI principal del kiosko
├── .htaccess            ← Restricción de acceso por IP
├── .gitignore
├── config.ejemplo.php   ← Plantilla (sí versionado)
├── config.php           ← Config real (NO versionado)
├── ip.php               ← Diagnóstico de IP pública
├── api/
│   └── marcar.php       ← Endpoint de marcaciones
├── css/
│   └── kiosko.css       ← Estilos del kiosko
├── js/
│   └── kiosko.js        ← Lógica JS del kiosko
└── docs/
    ├── OPERACION.md     ← Este archivo
    └── schema.sql       ← SQL para crear tablas
```
