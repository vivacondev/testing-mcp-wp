# testing-mcp-wp

## 2026-06-18

- Descargado WordPress (última versión) y extraído en la raíz del proyecto
- Creado `wp-config.php` con conexión a la base de datos en OVH:
  - Host: `khzxauntestmcp.mysql.db`
  - DB / Usuario: `khzxauntestmcp`
- Pendiente: completar instalación desde `tudominio.com/wp-admin/install.php` una vez sincronizado con OVH
- Creado `.gitignore` excluyendo `wp-config.php`, uploads, cache, upgrade y `.htaccess`
