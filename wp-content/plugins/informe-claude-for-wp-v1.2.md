# Claude for WP
## Informe de capacidades y propuesta de valor

**by Tech - viva! · vivaconversion.com**
**Versión 1.2 · Junio 2026**

---

## 1. Resumen ejecutivo

Claude for WP es un plugin de WordPress desarrollado por Tech - viva! que integra el modelo de lenguaje Claude (Anthropic) directamente en el panel de administración. La versión 1.2 añade la capacidad de crear páginas completas con widgets nativos de Elementor desde lenguaje natural, sin necesidad de abrir el editor visual.

El plugin combina tres modos de trabajo: asistencia conversacional con Tool Use (acciones reales en WP), generación de bloques HTML para Elementor, y creación de páginas Elementor nativas con estructura de secciones y columnas.

---

## 2. Módulos incluidos

| Módulo | Qué hace | Beneficio |
|---|---|---|
| 💬 Chat (página) | Conversación con Claude en página dedicada | Interacción detallada con contexto completo |
| 🤖 Widget flotante | Chatbot persistente en esquina inferior derecha de todo el admin | Acceso instantáneo sin cambiar de página |
| 📝 Contenido | Genera o mejora textos, extractos y meta descripciones SEO | Reduce tiempo de redacción hasta un 70% |
| 🎨 Elementor / HTML | Genera bloques HTML+CSS para pegar en widget HTML de Elementor | Prototipado rápido con preview en tiempo real |
| 🧩 Elementor Nativo | Crea páginas completas con widgets nativos de Elementor via IA | Páginas editables visualmente desde el primer momento |
| 🔧 Tool Use | Claude ejecuta acciones reales en WordPress vía API de Anthropic | Automatización directa sin código manual |
| ⚙️ Ajustes | Configura API key y modelo (Haiku / Sonnet / Opus) | Control de coste vs. calidad |

---

## 3. Widget flotante

Disponible en todas las páginas del admin de WordPress. Permite interactuar con Claude sin interrumpir el flujo de trabajo actual.

### 3.1 Características

- Botón circular fijo en la esquina inferior derecha de todo el admin
- Panel de chat con animación de apertura y cabecera de color
- Indicador de escritura animado mientras Claude procesa
- Pills de acciones ejecutadas visibles en tiempo real
- Botón de limpiar conversación en el header del panel

### 3.2 Historial de sesión

El historial se almacena en `sessionStorage` del navegador y persiste mientras la pestaña esté abierta. Al navegar entre secciones del admin la conversación se mantiene activa. Al cerrar el navegador el historial se limpia.

---

## 4. Elementor nativo — Novedad v1.2

La herramienta `create_elementor_page` permite a Claude generar páginas completas con la estructura de datos interna de Elementor. El resultado es una página lista para abrir en el editor visual, con widgets nativos correctamente configurados.

### 4.1 Cómo funciona

1. Claude recibe la instrucción en lenguaje natural desde el chat o widget flotante
2. Genera la estructura de secciones, columnas y widgets en JSON
3. Escribe el JSON en `_elementor_data` del post via `wp_postmeta`
4. Activa el modo builder de Elementor en la página
5. Devuelve el enlace directo al editor Elementor con la página lista

### 4.2 Widgets soportados

| Widget | Parámetros principales | Uso típico |
|---|---|---|
| `heading` | title, tag (h1-h6), align, color, font_size | Títulos de sección, headlines de hero |
| `text-editor` | content (HTML), align | Párrafos, descripciones, cuerpo de texto |
| `button` | text, url, align, size, bg_color, text_color | CTAs, enlaces de acción |
| `image` | image_id o url, caption, align, width | Imágenes de producto, ilustraciones |
| `spacer` | height (px) | Separación vertical entre bloques |
| `divider` | style, color, weight, width, align | Separadores visuales entre secciones |
| `icon-box` | icon (Font Awesome), title, description, position | Features, servicios, ventajas |
| `image-box` | image_id o url, title, description, position | Cards de equipo, productos, casos |
| `video` | provider (youtube/vimeo), url | Demos, testimonios en vídeo |

### 4.3 Diferencia con Elementor/HTML

| | Widget flotante / Chat | Sección Elementor/HTML |
|---|---|---|
| Resultado | Página Elementor con widgets nativos | Código HTML+CSS para pegar manualmente |
| Acción manual requerida | Solo abrir el editor Elementor | Copiar y pegar en widget HTML |
| Widgets nativos | ✓ Sí | ✗ No |
| Preview antes de crear | ✗ No | ✓ Sí |
| Editable en Elementor | ✓ Completamente | Limitado al widget HTML |

### 4.4 Ejemplos de uso

- *"Crea una landing page para un servicio de consultoría con hero, 3 servicios en columnas y un CTA final"*
- *"Crea una página de equipo con 4 image-box en dos columnas"*
- *"Crea una página de servicios con sección oscura de hero y tres icon-box debajo"*

---

## 5. Motor de Tool Use

Claude usa el protocolo nativo de herramientas de la API de Anthropic. Decide qué herramientas necesita, las ejecuta en el servidor WordPress, y devuelve un resumen de lo que hizo. La versión 1.2 amplía el set de tools a 12.

### 5.1 Herramientas disponibles

| Tool | Qué hace | Caso de uso |
|---|---|---|
| `get_posts` | Lista posts, páginas o custom post types | Buscar contenido antes de modificar |
| `create_post` | Crea post/página con título, contenido, categorías | Publicar sin entrar al editor |
| `update_post` | Modifica título, contenido, estado de un post | Ediciones rápidas o masivas |
| `delete_post` | Mueve a la papelera | Limpieza de contenido |
| `get_terms` | Lista categorías, etiquetas y taxonomías | Consulta previa a asignaciones |
| `create_term` | Crea categoría o etiqueta nueva | Organización de contenido |
| `get_site_info` | Lee nombre, tema, plugins activos, versión WP | Contexto del sitio antes de actuar |
| `update_site_option` | Modifica blogname, blogdescription y otras opciones | Cambios globales del sitio |
| `get_users` | Lista usuarios con rol y email | Auditoría y gestión de accesos |
| `get_plugins` | Lista plugins con estado activo/inactivo | Diagnóstico rápido |
| `get_media` | Lista archivos de la biblioteca de medios | Referencia de recursos existentes |
| `create_elementor_page` | Crea página con widgets nativos de Elementor | Landing pages y páginas editables |

### 5.2 Bucle agentico

La clase `CFW_Api` implementa un bucle iterativo (máximo 10 iteraciones) que permite encadenar varias herramientas en una sola petición. Por ejemplo: `get_terms` para verificar si existe una categoría → `create_term` si no existe → `create_elementor_page` con la estructura solicitada.

---

## 6. Arquitectura técnica

| Archivo | Responsabilidad |
|---|---|
| `claude-for-wp.php` | Entry point. Carga las 5 clases y registra `plugins_loaded` |
| `class-cfw-settings.php` | Gestiona API key y modelo via Settings API de WordPress |
| `class-cfw-api.php` | `send()` para llamadas simples y `send_with_tools()` para el bucle agentico |
| `class-cfw-tools.php` | Define las 12 herramientas en JSON Schema e implementación PHP. Incluye el builder de Elementor con factory de widgets |
| `class-cfw-admin.php` | Registra menús, encola assets de forma condicional, renderiza el widget flotante via `admin_footer` |
| `class-cfw-ajax.php` | Cuatro endpoints AJAX: `cfw_chat`, `cfw_content`, `cfw_elementor`, `cfw_create_post` |
| `assets/css/widget.css` + `assets/js/widget.js` | Assets del widget flotante. Cargan en todo el admin. El JS gestiona historial en `sessionStorage` |

---

## 7. Limitaciones y hoja de ruta

| Limitación actual | Evolución posible |
|---|---|
| Sin contexto automático del sitio | Inyectar datos (tema, plugins, entradas) en el system prompt al abrir el widget |
| Historial no persiste entre sesiones | Guardar conversaciones en BD con `post_meta` o tabla propia |
| Elementor Pro no soportado | Mapear widgets Pro (Forms, Slides, Posts) uno a uno |
| Sin preview Elementor antes de crear | Generar HTML preview antes de escribir el JSON definitivo |
| Solo rol `manage_options` | Añadir capacidades personalizadas por módulo |
| Sin webhook de deploy automático | Configurar `git-pull.php` + webhook GitHub para CI/CD completo |

---

## 8. Requisitos

- WordPress 6.0 o superior
- Elementor (free) instalado y activo para la funcionalidad de páginas nativas
- PHP 8.1 o superior
- API key de Anthropic ([console.anthropic.com](https://console.anthropic.com))
- Acceso de administrador al sitio WordPress
- Conexión saliente al dominio `api.anthropic.com` desde el servidor

---

## 9. Conclusión

La versión 1.2 de Claude for WP completa el ciclo de trabajo con Elementor: desde la generación de bloques HTML para casos simples hasta la creación de páginas completas con widgets nativos para proyectos más elaborados. El widget flotante hace que todo esto esté disponible en cualquier punto del admin sin cambiar de contexto. La arquitectura modular permite seguir ampliando capacidades de forma incremental.

---

*Claude for WP · Tech - viva! · vivaconversion.com*
