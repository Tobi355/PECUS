# PECUS — Project Specification

## 1. Información general

**Nombre del proyecto:** PECUS

**Tipo:** Plataforma web de gestión y automatización de finanzas personales.

**Objetivo general:**

> Desarrollar una plataforma web capaz de centralizar y automatizar el registro, organización y visualización de la información financiera personal, reduciendo al mínimo la intervención manual del usuario.

### Conceptos de marca

**ORDEN · CLARIDAD · CONTROL · BALANCE**

### Significado del nombre

PECUS proviene del latín y significa ganado o animales de pastoreo. El término se utilizaba para referirse a animales domesticados, especialmente aquellos destinados a la producción y administración de recursos.

De *pecus* deriva *pecunia*, palabra latina relacionada con el dinero, la riqueza y los bienes.

PECUS toma al ganado como metáfora de los recursos económicos que pueden encontrarse dispersos y que necesitan ser reunidos, organizados y administrados.

**Reunir. Ordenar. Comprender. Controlar.**

---

# 2. Concepto del producto

PECUS busca centralizar la información financiera personal y reducir tanto:

1. la carga de registrar manualmente cada movimiento;
2. la carga de mantener y organizar continuamente las finanzas.

La idea central es:

> El usuario continúa con su vida financiera normal. PECUS recibe información, la procesa, la organiza y solicita intervención únicamente cuando es necesario.

Flujo conceptual:

```text
VIDA FINANCIERA DEL USUARIO
            ↓
     PECUS recibe información
            ↓
      Procesa / interpreta
            ↓
        Valida datos
            ↓
       Organiza información
            ↓
      Registra movimientos
            ↓
     Dashboard / análisis
            ↓
       Usuario supervisa
```

Principio UX central:

> **Automatizar primero, preguntar cuando sea necesario y permitir intervenir siempre que el usuario quiera.**

Regla de seguridad de datos:

> **PECUS nunca debe inventar información financiera. Cuando un dato no pueda determinarse con suficiente confianza, debe solicitar revisión o intervención del usuario.**

---

# 3. Problema

La información financiera personal suele encontrarse distribuida entre:

* bancos;
* tarjetas;
* billeteras virtuales;
* efectivo;
* comprobantes;
* transferencias;
* diferentes aplicaciones y servicios.

Además, registrar y organizar manualmente cada movimiento requiere tiempo y mantenimiento constante.

PECUS busca centralizar esa información y automatizar el proceso de registro y organización.

El problema principal abordado no es únicamente visualizar las finanzas, sino **reducir el esfuerzo necesario para mantenerlas organizadas**.

---

# 4. Público objetivo

PECUS está pensado para personas que administran sus propias finanzas personales.

Especialmente usuarios que utilizan múltiples:

* cuentas bancarias;
* tarjetas;
* billeteras virtuales;
* medios de pago;
* fuentes de ingreso;
* comprobantes.

No se limita exclusivamente a usuarios con conocimientos financieros.

---

# 5. Propuesta de valor

PECUS centraliza y automatiza la administración financiera personal.

En lugar de obligar al usuario a registrar y organizar manualmente cada operación:

```text
Información financiera dispersa
             ↓
            PECUS
             ↓
Procesamiento y organización
             ↓
Información financiera centralizada
```

El objetivo es que el usuario dedique menos tiempo a cargar y organizar información y más tiempo a utilizarla.

---

# 6. Alcance funcional

## 6.1 Estructura general

```text
PECUS
│
├── Público
│   ├── Landing
│   ├── Registro
│   └── Inicio de sesión
│
└── Aplicación
    ├── Dashboard
    ├── Movimientos
    ├── Comprobantes
    ├── Fuentes
    ├── Categorías
    ├── Análisis
    ├── Metas
    ├── Presupuestos
    ├── Asistente financiero
    ├── Notificaciones
    └── Configuración
```

---

# 7. MVP funcional

Las siguientes funcionalidades deben formar parte de la primera versión funcional de PECUS.

## 7.1 Autenticación

* Registro.
* Inicio de sesión.
* Email + contraseña.
* Inicio de sesión con Google.
* Recuperación de contraseña.
* Verificación de email cuando corresponda.
* Protección de rutas.
* Roles y permisos básicos.

### Perfil

* Nombre.
* Apellido.
* Email.
* Foto.
* Teléfono.

No se considera necesaria la fecha de nacimiento.

---

# 8. Onboarding

Después del registro, el usuario debe poder preparar su cuenta.

Proceso:

```text
Registro
   ↓
Onboarding
   ↓
Agregar fuentes
   ↓
Definir saldos iniciales
   ↓
Configurar categorías
   ↓
Dashboard
```

El sistema debe proporcionar categorías predeterminadas, pero permitir modificarlas.

---

# 9. Fuentes financieras

Una fuente representa un lugar o medio desde el cual se administra dinero.

Ejemplos:

* Banco.
* Cuenta bancaria.
* Tarjeta de crédito.
* Billetera virtual.
* Efectivo.
* Otro medio financiero.

Las tarjetas de crédito se consideran fuentes financieras desde el comienzo.

## Datos principales

* Nombre.
* Tipo.
* Moneda.
* Saldo inicial.
* Estado.
* Fecha de creación.

## Funcionalidades

* Crear.
* Editar.
* Archivar.
* Consultar movimientos.
* Consultar saldo.
* Consultar estadísticas.

### Saldo

El saldo se obtiene conceptualmente mediante:

```text
Saldo inicial
+
Movimientos que incrementan saldo
-
Movimientos que reducen saldo
=
Saldo actual
```

El usuario no debe mantener manualmente el saldo actual.

---

# 10. Movimientos

Los movimientos representan las operaciones financieras registradas por PECUS.

## Tipos

```text
EXPENSE
INCOME
INTERNAL_TRANSFER
EXTERNAL_TRANSFER
REFUND
```

### Expense

Representa un gasto.

### Income

Representa un ingreso.

### Internal transfer

Representa un movimiento de dinero entre dos fuentes pertenecientes al mismo usuario.

Ejemplo:

```text
Santander
   - $100.000
       ↓
Mercado Pago
   + $100.000
```

No debe contabilizarse como ingreso ni gasto.

Una transferencia interna se representa mediante dos movimientos relacionados.

### External transfer

Transferencia hacia o desde otra persona/entidad.

Su tratamiento financiero dependerá de su significado.

### Refund

Representa una devolución/reintegro.

Debe conservar relación con el movimiento original cuando corresponda.

No se debe eliminar el gasto original para representar una devolución.

---

# 11. Atributos de movimientos

Un movimiento puede tener:

* Usuario.
* Fuente.
* Categoría.
* Tipo.
* Estado.
* Origen.
* Monto.
* Moneda.
* Descripción.
* Fecha de operación.
* Notas.
* Comprobante.
* Relación con transferencia.
* Relación con movimiento original cuando corresponda.

## Origen

Ejemplos:

```text
MANUAL
RECEIPT
SHARED
CSV
API
AUTOMATIC
```

## Estado

Debe poder contemplar situaciones como:

```text
PENDING
CONFIRMED
```

y otros estados que resulten necesarios durante la implementación.

---

# 12. Cuotas

En el MVP una compra en cuotas puede representarse inicialmente como información asociada al movimiento.

Ejemplo:

```text
Notebook
$600.000
6 cuotas
```

La gestión avanzada de:

* cuotas futuras;
* vencimientos;
* consumo pendiente;
* cierre de tarjeta;
* pago de tarjeta;

queda para una etapa posterior.

---

# 13. Comprobantes

Los comprobantes son uno de los elementos centrales de PECUS.

## Entradas posibles

* JPG.
* PNG.
* PDF.
* Fotografía.
* Captura de pantalla.
* Comprobante compartido.
* CSV/Excel en etapas posteriores.
* Integraciones externas en el futuro.

## Estados

```text
RECIBIDO
PROCESANDO
PROCESADO
CONFIRMADO
REQUIERE_REVISION
ERROR
```

## Datos a extraer

Principalmente:

* Monto.
* Fecha.
* Hora cuando esté disponible.
* Comercio/persona.
* Tipo de operación.
* Moneda.
* Fuente.
* Categoría.

Datos secundarios posibles:

* Número de comprobante.
* CUIT.
* Método de pago.
* ID de operación.
* Descripción.
* Productos.
* Subtotal.
* Impuestos.

---

# 14. Procesamiento de comprobantes

Flujo:

```text
Comprobante recibido
        ↓
Guardar archivo
        ↓
Identificar tipo de documento
        ↓
OCR / procesamiento
        ↓
Extraer información
        ↓
Interpretar información
        ↓
Clasificar
        ↓
Detectar duplicados
        ↓
Calcular confianza
        ↓
¿Confianza suficiente?
       / \
     Sí   No
     ↓     ↓
Registrar  Revisar
automático
```

## Tipos de documento

El sistema debe intentar distinguir:

* Comprobante de pago.
* Ticket de compra.
* Factura.
* Transferencia.
* Resumen.
* Documento desconocido/no financiero.

---

# 15. Confianza

El procesamiento debe contemplar un nivel de confianza.

Conceptualmente:

```text
ALTA
  ↓
Registro automático

MEDIA
  ↓
Propuesta + revisión

BAJA
  ↓
Revisión obligatoria
```

PECUS no debe inventar datos para completar un movimiento.

---

# 16. Excepciones de procesamiento

### Comprobante ilegible

Permitir:

* reintentar;
* cargar otro archivo;
* completar manualmente.

### Monto faltante

Solicitar al usuario.

### Múltiples montos

Intentar identificar el total.

Si no existe suficiente confianza:

> solicitar revisión.

### Comercio desconocido

Permitir:

> Comercio desconocido

y posterior edición.

### Categoría desconocida

Asignar:

> Sin categorizar

sin bloquear el movimiento.

### Fuente desconocida

Solicitar selección del usuario.

### Documento inválido

Informar que el archivo no corresponde a un documento financiero compatible.

### Posible duplicado

Mostrar advertencia y permitir:

* ver movimiento existente;
* cancelar;
* registrar igualmente.

### Error técnico

Diferenciar:

```text
ERROR TÉCNICO
No se pudo procesar.

INCERTIDUMBRE
El documento fue procesado,
pero algunos datos requieren revisión.
```

---

# 17. Compartir comprobantes

Experiencia objetivo:

```text
Mercado Pago / MODO / Ualá / Banco
                ↓
             Compartir
                ↓
             PECUS
                ↓
          Procesamiento
                ↓
       Extracción de datos
                ↓
       Validación/confianza
                ↓
        Movimiento creado
```

La experiencia de compartir directamente con PECUS forma parte del producto.

La disponibilidad exacta de esta funcionalidad dependerá de las capacidades de la plataforma web y, eventualmente, de una aplicación nativa.

---

# 18. Categorías

## Categorías de gastos

* Alimentación.
* Transporte.
* Vivienda.
* Servicios.
* Salud.
* Educación.
* Entretenimiento.
* Compras.
* Suscripciones.
* Impuestos.
* Otros.

## Categorías de ingresos

* Sueldo.
* Freelance.
* Ventas.
* Inversiones.
* Otros.

## Funciones

* Crear.
* Editar.
* Archivar.
* Elegir icono.
* Elegir color.
* Sugerencias automáticas.
* Aprendizaje a partir de correcciones.

---

# 19. Dashboard

El Dashboard será el centro de control de PECUS.

## Información

* Dinero disponible.
* Ingresos.
* Gastos.
* Balance.
* Evolución.
* Gastos por categoría.
* Últimos movimientos.
* Fuentes.
* Alertas.
* Información relevante.

## Acciones rápidas

* Nuevo movimiento.
* Cargar comprobante.
* Nueva fuente.
* Revisar pendientes.

## Personalización

El dashboard será personalizable.

Tendrá selector de período:

* Hoy.
* Semana.
* Mes.
* Año.
* Personalizado.

---

# 20. Análisis

El módulo permitirá analizar:

* Ingresos.
* Gastos.
* Balance.
* Categorías.
* Fuentes.
* Evolución.
* Comparaciones.

Períodos:

* Día.
* Semana.
* Mes.
* Año.
* Personalizado.

La búsqueda mediante lenguaje natural queda para una etapa futura.

---

# 21. Búsqueda

PECUS contará con búsqueda global.

En el MVP será búsqueda tradicional.

Ejemplo:

```text
Supermercado
Mercado Pago
Netflix
$50.000
```

La búsqueda mediante lenguaje natural queda para el futuro:

> "¿Cuánto gasté en comida en agosto?"

---

# 22. Notificaciones

PECUS podrá generar notificaciones sobre:

* Comprobante procesado.
* Error de comprobante.
* Movimiento que requiere revisión.
* Posible duplicado.
* Presupuesto próximo al límite.
* Presupuesto excedido.
* Progreso de meta.
* Gasto inusual.
* Movimiento importante.

## Canales

* Dentro de PECUS.
* Notificaciones del navegador/dispositivo.
* Email.

El canal puede depender del tipo de notificación.

---

# 23. Exportación

El usuario podrá exportar información en:

* CSV.
* Excel.
* PDF.

También existirá:

> Descargar todos mis datos.

La exportación forma parte del control y portabilidad de la información del usuario.

---

# 24. Monedas

El MVP utilizará ARS como moneda principal.

Sin embargo, la arquitectura debe estar preparada para soportar otras monedas.

La conversión automática a ARS para estadísticas queda para una etapa futura.

---

# 25. Importación histórica

Se contempla la importación de:

* CSV.
* Excel.

La interpretación automática de esos archivos y conversión inteligente a movimientos queda para una etapa posterior.

---

# 26. Recurrencias

PECUS podrá detectar automáticamente gastos recurrentes.

Ejemplo:

```text
Netflix
↓
Todos los meses
↓
Monto similar
↓
Detectar recurrencia
```

También podrá generar recordatorios para cargos esperados.

---

# 27. Metas

Segunda etapa.

Una meta tendrá:

* Nombre.
* Monto objetivo.
* Fecha objetivo.
* Monto acumulado.
* Progreso.
* Moneda.
* Estado.

Ejemplo:

```text
Viaje
Objetivo: $2.000.000
Ahorrado: $850.000
Progreso: 42,5%
```

---

# 28. Presupuestos

Segunda etapa.

Funciones:

* Presupuesto mensual.
* Presupuesto por categoría.
* Gasto acumulado.
* Disponible.
* Porcentaje utilizado.
* Alertas.

---

# 29. Asistente financiero

El asistente será una funcionalidad futura.

No será un chatbot genérico.

Debe poder utilizar información financiera del usuario para responder preguntas como:

* cuánto gastó en determinada categoría;
* cuáles fueron sus mayores gastos;
* cuánto destinó a suscripciones;
* cómo avanza una meta;
* qué movimientos ocurrieron durante un período.

En etapas posteriores podrá incorporar análisis más avanzados.

---

# 30. Automatización futura

Queda previsto:

* Reglas creadas por el usuario.
* Reglas sugeridas por PECUS.
* Automatizaciones personalizadas.
* Análisis de patrones.
* Integraciones financieras.

---

# 31. Integraciones

PECUS deberá poder integrarse, cuando técnicamente sea posible, con:

* Bancos.
* Mercado Pago.
* MODO.
* Ualá.
* Tarjetas.
* Otras billeteras.
* Servicios financieros mediante APIs.

La arquitectura no debe quedar atada a una única integración.

---

# 32. Seguridad

## MVP

* Autenticación.
* Email + contraseña.
* Google.
* Recuperación de contraseña.
* Protección de rutas.
* Roles/permisos.
* Eliminación lógica.
* Historial de modificaciones.

## Futuro

* 2FA.
* Gestión avanzada de sesiones/dispositivos.
* Controles adicionales de seguridad.

---

# 33. Arquitectura técnica

PECUS utilizará una arquitectura con frontend separado y backend mediante API.

```text
FRONTEND
HTML
CSS
Bootstrap
JavaScript
       │
       │ HTTP + JSON
       ▼
BACKEND
Laravel + PHP
       │
       ▼
MySQL / MariaDB
```

El frontend no accederá directamente a la base de datos.

Toda operación de datos pasará por el backend.

---

# 34. Frontend

Tecnologías:

* HTML.
* CSS.
* Bootstrap.
* JavaScript.
* Lucide Icons.

No se utilizará React o Vue inicialmente.

La prioridad es mantener el frontend:

* simple;
* organizado;
* mantenible;
* responsive;
* visualmente consistente.

La aplicación será responsive para desktop y mobile.

---

# 35. Sistema de navegación

PECUS **no utilizará una navbar superior tradicional como navegación principal**.

La navegación principal será una **sidebar vertical minimalista ubicada en el borde izquierdo**.

Características:

* retraída inicialmente;
* iconos visibles;
* expansión para mostrar nombres;
* tooltips en estado retraído;
* navegación agrupada;
* logo de PECUS;
* estado activo claramente identificable;
* configuración/notificaciones/perfil en zona inferior;
* comportamiento responsive.

Conceptualmente:

```text
┌──────┐
│ PECUS│
├──────┤
│  ◉   │
│  ◉   │
│  ◉   │
│  ◉   │
│  ◉   │
│      │
│      │
│  ⚙   │
│  ◉   │
└──────┘
```

Expandida:

```text
┌──────────────────────┐
│ PECUS                │
├──────────────────────┤
│ Dashboard            │
│ Movimientos          │
│ Comprobantes         │
│ Fuentes              │
│ Categorías            │
│ Análisis              │
│ Metas                │
│ Presupuestos         │
│ Asistente financiero │
│                      │
│ Configuración        │
└──────────────────────┘
```

La referencia visual utilizada para el comportamiento es una sidebar flotante/minimalista con expansión, tooltips y microinteracciones.

No se copiará literalmente el diseño de referencia.

---

# 36. Iconografía

PECUS utilizará **Lucide Icons**.

Los iconos deben:

* representar claramente cada sección;
* mantener consistencia visual;
* evitar exceso de iconos decorativos;
* utilizarse como apoyo a la comprensión;
* mantener tamaños y stroke consistentes.

La elección concreta de iconos se hará según cada funcionalidad.

---

# 37. Design System

La identidad visual oficial es:

## Colores

```text
Primary Dark
#0A3B25

Primary
#2A6151

Neutral
#B2B7AA

Background
#FCF7F0

Accent
#D8C2A4
```

## Tipografías

### Principal

**Gopadel Medium**

Uso:

* títulos;
* navegación;
* números importantes;
* botones;
* elementos principales de marca.

### Secundaria

**Souvenir Std Light Italic**

Uso:

* frases;
* subtítulos;
* elementos editoriales;
* destacados;
* detalles de identidad.

Las fuentes serán incorporadas mediante archivos locales proporcionados por el proyecto.

---

# 38. Principios visuales

PECUS debe transmitir:

* orden;
* claridad;
* control;
* equilibrio;
* confianza;
* modernidad;
* calidez.

Debe evitar:

* apariencia de banco tradicional;
* dashboard corporativo genérico;
* exceso de colores;
* exceso de sombras;
* exceso de tarjetas;
* interfaces saturadas;
* decoración sin función.

La interfaz debe priorizar:

> **Jerarquía visual + información clara + acciones simples.**

---

# 39. Componentes frontend

Se deberán crear componentes reutilizables para:

* Sidebar.
* Tooltips.
* Buttons.
* Cards.
* Inputs.
* Selects.
* Tables.
* Badges.
* Alerts.
* Modals.
* Dropdowns.
* Tabs.
* Progress bars.
* Empty states.
* Loading states.
* Error states.
* Charts.
* Notifications.
* File upload.
* Receipt review.
* Movement rows.

Los componentes deben utilizar el Design System y evitar estilos duplicados.

---

# 40. Organización del frontend

Estructura inicial propuesta:

```text
frontend/
│
├── index.html
│
├── pages/
│   ├── login.html
│   ├── register.html
│   ├── onboarding.html
│   ├── dashboard.html
│   ├── movements.html
│   ├── receipts.html
│   ├── sources.html
│   ├── categories.html
│   ├── analysis.html
│   ├── goals.html
│   ├── budgets.html
│   ├── assistant.html
│   └── settings.html
│
├── assets/
│   ├── css/
│   │   ├── variables.css
│   │   ├── global.css
│   │   ├── components.css
│   │   └── pages.css
│   │
│   ├── js/
│   │   ├── app.js
│   │   ├── components.js
│   │   └── pages/
│   │
│   ├── fonts/
│   │
│   └── images/
│
└── components/
```

Esta estructura puede evolucionar durante la implementación si existe una razón técnica clara.

---

# 41. Layout reutilizable

No se debe copiar manualmente la sidebar en cada página.

JavaScript podrá cargar o construir los elementos compartidos del layout:

```text
Layout
├── Sidebar
├── Main content
└── Shared components
```

El contenido específico de cada página será independiente.

---

# 42. Backend

Tecnologías:

* PHP.
* Laravel.
* REST API.
* Eloquent ORM.
* MySQL/MariaDB.

La estructura deberá mantenerse simple.

Conceptualmente:

```text
Request
   ↓
Controller
   ↓
Service cuando sea necesario
   ↓
Model / Eloquent
   ↓
Database
```

No se deben implementar patrones o capas adicionales sin una necesidad concreta.

La complejidad debe justificarse por:

* mantenimiento;
* eficiencia;
* seguridad;
* escalabilidad;
* separación clara de responsabilidades.

No se agregará complejidad únicamente por seguir patrones.

---

# 43. Servicios

Los Services se utilizarán cuando exista lógica de negocio suficientemente compleja.

Ejemplo:

```text
ReceiptController
        ↓
ReceiptProcessingService
        ↓
OCR
        ↓
Extraction
        ↓
Classification
        ↓
Validation
        ↓
Movement
```

Operaciones sencillas no deberán dividirse innecesariamente en múltiples capas.

---

# 44. API

La comunicación entre frontend y backend será mediante API REST utilizando JSON.

Ejemplos conceptuales:

```text
GET    /api/movements
GET    /api/movements/{id}
POST   /api/movements
PUT    /api/movements/{id}
DELETE /api/movements/{id}
```

Otros recursos:

```text
/api/auth
/api/users
/api/sources
/api/categories
/api/receipts
/api/notifications
/api/analysis
/api/goals
/api/budgets
```

Los endpoints concretos se definirán durante la implementación del backend.

---

# 45. Base de datos

Motor inicial:

**MySQL / MariaDB**

El diseño debe ser compatible con la arquitectura Laravel + Eloquent.

---

# 46. Entidades principales

## users

```text
id
name
surname
email
password
photo
phone
google_id
email_verified_at
created_at
updated_at
deleted_at
```

## sources

```text
id
user_id
name
type
currency
initial_balance
status
created_at
updated_at
deleted_at
```

Tipos conceptuales:

```text
BANK
CREDIT_CARD
WALLET
CASH
OTHER
```

## categories

```text
id
user_id
name
type
icon
color
is_default
created_at
updated_at
deleted_at
```

Tipos:

```text
EXPENSE
INCOME
```

## movements

```text
id
user_id
source_id
category_id
type
status
origin
amount
currency
description
operation_date
notes
transfer_group_id
original_movement_id
created_at
updated_at
deleted_at
```

Los campos exactos podrán ajustarse durante la creación de migraciones.

## receipts

```text
id
user_id
movement_id
file_path
file_name
mime_type
status
confidence
extracted_data
error_message
processed_at
created_at
updated_at
```

`extracted_data` podrá utilizar JSON.

## notifications

```text
id
user_id
type
title
message
data
read_at
created_at
updated_at
```

## goals

```text
id
user_id
name
target_amount
current_amount
currency
target_date
status
created_at
updated_at
deleted_at
```

## budgets

```text
id
user_id
category_id
amount
currency
period
start_date
end_date
created_at
updated_at
```

## user_preferences

```text
id
user_id
settings
created_at
updated_at
```

`settings` puede utilizar JSON.

## activity_logs

```text
id
user_id
entity_type
entity_id
action
old_values
new_values
created_at
```

Los campos exactos serán revisados durante la implementación.

---

# 47. Relaciones principales

```text
USER
 │
 ├── SOURCES
 │      │
 │      └── MOVEMENTS
 │
 ├── CATEGORIES
 │      │
 │      └── MOVEMENTS
 │
 ├── RECEIPTS
 │      │
 │      └── MOVEMENTS
 │
 ├── GOALS
 │
 ├── BUDGETS
 │
 ├── NOTIFICATIONS
 │
 ├── USER_PREFERENCES
 │
 └── ACTIVITY_LOGS
```

---

# 48. Archivos

Los archivos de comprobantes no deben almacenarse directamente como imágenes/PDF completos dentro de la base de datos.

La base de datos almacenará información sobre el archivo:

```text
file_path
file_name
mime_type
status
...
```

El archivo físico será almacenado mediante el sistema de almacenamiento de Laravel.

La arquitectura debe permitir posteriormente migrar de almacenamiento local a almacenamiento externo sin rediseñar la entidad Receipt.

---

# 49. Automatización

Desde el MVP se busca automatizar:

* OCR.
* Extracción de monto.
* Extracción de fecha.
* Identificación del comercio.
* Identificación del tipo.
* Sugerencia de categoría.
* Identificación de fuente cuando sea posible.
* Detección de duplicados.
* Detección de recurrencias.
* Aprendizaje de correcciones.

---

# 50. Arquitectura de procesamiento

Todos los caminos de entrada deben converger en una lógica común:

```text
Manual
Receipt
Shared
CSV
Future API
      │
      ▼
    PECUS
      │
      ▼
Process
      │
      ▼
Interpret
      │
      ▼
Validate
      │
      ▼
Movement
```

Esto evita crear una lógica completamente diferente para cada origen.

---

# 51. Fuera del MVP

Las siguientes funcionalidades quedan fuera de la primera versión funcional:

* Conversión automática de monedas.
* Búsqueda en lenguaje natural.
* Gestión avanzada de tarjetas.
* Gestión completa de cuotas.
* Importación inteligente avanzada.
* Reglas automáticas creadas por usuarios.
* Reglas sugeridas por PECUS.
* Integraciones bancarias.
* Integraciones con billeteras.
* Asistente financiero completo.
* Análisis financiero avanzado.
* 2FA.
* Gestión avanzada de sesiones/dispositivos.

Estas funcionalidades pueden aparecer visualmente en el frontend como parte del producto completo, utilizando estados de:

* próximamente;
* beta;
* bloqueado;
* ejemplo;
* datos simulados.

No deben presentarse como funcionalidades realmente operativas hasta ser implementadas.

---

# 52. Frontend-first

El desarrollo inicial priorizará la construcción del frontend completo.

Objetivo:

> Tener una representación visual completa de PECUS antes de dedicar el mayor esfuerzo al backend.

El frontend inicialmente utilizará datos simulados.

Esto permitirá:

* validar la experiencia;
* validar la navegación;
* validar el diseño;
* mostrar el producto completo;
* identificar problemas UX;
* definir claramente qué datos necesitará cada endpoint.

Posteriormente los datos simulados serán reemplazados por llamadas a la API.

---

# 53. Orden de construcción del frontend

## Etapa 1 — Design System

* Variables.
* Tipografías.
* Colores.
* Espaciado.
* Bordes.
* Sombras.
* Botones.
* Inputs.
* Cards.
* Badges.
* Estados.

## Etapa 2 — Layout

* Sidebar retraída.
* Sidebar expandida.
* Tooltips.
* Navegación.
* Logo.
* Perfil.
* Notificaciones.
* Responsive.

## Etapa 3 — Dashboard

* Resumen financiero.
* Cards.
* Gráficos.
* Últimos movimientos.
* Alertas.
* Acciones rápidas.

## Etapa 4 — Aplicación

* Movimientos.
* Comprobantes.
* Fuentes.
* Categorías.
* Análisis.
* Metas.
* Presupuestos.
* Asistente.
* Configuración.
* Notificaciones.

## Etapa 5 — Público

* Landing.
* Login.
* Registro.
* Onboarding.

## Etapa 6 — Interacciones

* Modales.
* Formularios.
* Filtros.
* Búsqueda.
* Estados.
* Upload.
* Revisión de comprobantes.
* Responsive.

---

# 54. Principios de desarrollo

1. Mantener la solución simple cuando sea suficiente.
2. No agregar complejidad sin beneficio concreto.
3. Evitar duplicación.
4. Utilizar componentes reutilizables.
5. Mantener separación entre frontend y backend.
6. Mantener API como único acceso del frontend a los datos.
7. Diseñar pensando en responsive desde el principio.
8. No bloquear el MVP por funcionalidades futuras.
9. Preparar la arquitectura para crecer sin sobrediseñarla.
10. Priorizar claridad del código.
11. Priorizar seguridad en datos financieros.
12. Mantener una experiencia visual consistente.
13. No presentar como funcional una característica que solamente está simulada.
14. Utilizar datos ficticios claramente identificables durante el desarrollo frontend.
15. Documentar decisiones técnicas importantes.

---

# 55. Estado del proyecto

### Fase 1 — Definición general

**COMPLETADA**

### Fase 2 — Funcionalidades

**COMPLETADA**

### Fase 3 — Arquitectura técnica

**Definición conceptual completada**

### Próximo objetivo

**Construcción del frontend completo de PECUS.**

Después:

```text
Frontend completo
       ↓
Backend Laravel
       ↓
Base de datos
       ↓
API
       ↓
Integración frontend/backend
       ↓
Procesamiento de comprobantes
       ↓
Automatización
       ↓
Integraciones externas
```

---

# 56. Regla para futuras decisiones

Cuando aparezca una decisión técnica no contemplada en este documento:

1. elegir primero la solución más simple que resuelva correctamente el problema;
2. evaluar impacto en seguridad, mantenimiento y escalabilidad;
3. evitar complejidad prematura;
4. actualizar este documento cuando la decisión afecte la arquitectura general.

`PROJECT_SPEC.md` funciona como documento maestro del proyecto y debe mantenerse actualizado cuando cambien decisiones estructurales.
