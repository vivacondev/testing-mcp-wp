# testing-mcp-wp

## 2026-06-18

- Descargado WordPress (última versión) y extraído en la raíz del proyecto
- Creado `wp-config.php` con conexión a la base de datos en OVH:
  - Host: `khzxauntestmcp.mysql.db`
  - DB / Usuario: `khzxauntestmcp`
- Pendiente: completar instalación desde `tudominio.com/wp-admin/install.php` una vez sincronizado con OVH
- Creado `.gitignore` excluyendo `wp-config.php`, uploads, cache, upgrade y `.htaccess`
- Desarrollado plugin **Claude for WP v1.2** (integración Claude/Anthropic en el admin de WordPress):
  - Chat en página dedicada y widget flotante persistente en todo el admin
  - Tool Use con 12 herramientas (crear/editar posts, páginas, términos, media, opciones del sitio)
  - Generación de bloques HTML+CSS para Elementor con preview
  - Creación de páginas Elementor nativas con widgets nativos via `create_elementor_page`
  - Bucle agéntico (máx. 10 iteraciones) para encadenar herramientas en una sola petición
  - Modelos configurables: Haiku / Sonnet / Opus
