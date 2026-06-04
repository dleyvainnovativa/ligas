# Guía del Manager — Padel Leagues

## 1. Tu primera liga en 5 minutos

1. **Inicia sesión** en /login con tu cuenta de Firebase.
2. En el dashboard, haz clic en **"Crear mi primera liga"** (o en Ligas → Nueva liga).
3. Llena los datos: nombre, formato (individual o parejas), número de jornadas, costo por jugador, días de la semana, horarios y reglas de penalización.
4. Guarda y sube un banner (opcional pero recomendado).
5. En la pestaña **Configuración**, define el estado: **Activa** para que sea visible al público.

## 2. Sedes y pistas

En la pestaña **Resumen** → Sedes y pistas:
- Agrega cada sede (club, ubicación).
- Dentro de cada sede, agrega las pistas físicas.
- Estos son los espacios físicos donde se jugarán los partidos.

## 3. Jugadores

En la pestaña **Jugadores**:
- Agrega uno por uno o importa un CSV con columnas: `nombre, email, telefono, pagado`.
- Marca el pago de cada jugador (No pagado / Parcial / Pagado).

## 4. Grupos (divisiones)

En la pestaña **Grupos**:
- Crea las divisiones (División A, División B, etc.).
- Arrastra jugadores desde el panel lateral a los grupos.
- Si tu liga es por parejas, primero crea las parejas en la sección **Parejas**, luego arrástralas a los grupos.

## 5. Jornadas y canchas

Dentro de cada grupo, haz clic en **"Jornadas de este grupo"**:
- Crea una jornada (Jornada 1, 2, ...).
- En el detalle de la jornada, arrastra jugadores (o parejas) del pool a las canchas.
- Cada cancha tiene máximo 4 jugadores (2 parejas).
- Usa **Auto-asignar** para que el sistema reparta a los miembros automáticamente.

## 6. Programar partidos

Desde el detalle de una jornada, haz clic en **Programar partidos**:
- Verás una cuadrícula: filas con pistas y horarios, columnas con fechas.
- Arrastra las canchas del panel lateral a las celdas (fecha + horario + pista).
- En modo individual, al arrastrar la **rotación 1** se programan automáticamente las 3 rotaciones en horarios consecutivos en la misma pista.
- Para mover un partido: arrástralo a otra celda.
- Para quitar un partido del calendario: haz clic en la X.

## 7. Resultados

En el calendario, haz clic en el icono ✎ de un partido para abrir el formulario de resultado.
- Ingresa los sets (ejemplo: 6-4, 3-6, 7-5).
- Marca No-show o Suplente si aplica.
- Guarda.

Los **standings se actualizan automáticamente** al guardar.

## 8. Propuestas de resultado (público)

Los jugadores pueden proponer marcadores desde la página pública. En el panel de la liga verás un badge **"N propuestas"** cuando haya propuestas pendientes.

Al abrir el formulario de resultado, los sets se pre-llenan con la propuesta. Tú solo apruebas (guardar) o editas y guardas. También puedes rechazar.

## 9. Anuncios

En la pestaña **Anuncios** puedes subir imágenes horizontales (1200×400 ideal) que se mostrarán en la página pública de la liga como un carrusel. Útil para patrocinadores o promociones.

## 10. Página pública

Cada liga tiene una URL única: `tudominio.com/{slug-de-la-liga}`.
- Comparte el link con tus jugadores (botón "Compartir" en el banner público).
- La página muestra standings en vivo, próximos partidos y resultados recientes.
- Los jugadores pueden proponer marcadores desde ahí.

## Preguntas frecuentes

**¿Cómo cambio el orden de los grupos?**
Por ahora, los grupos se ordenan por su orden de creación. Para reordenar, contacta al administrador.

**¿Puedo borrar un jugador a media liga?**
Sí, pero perderás sus estadísticas históricas. Considera moverlo a otro grupo si solo necesitas separarlo.

**¿Qué pasa si un jugador no llega?**
Marca **No-show** en el formulario de resultado. Se restará el puntaje configurado en las reglas de penalización.

**¿La página pública es gratis para los jugadores?**
Sí, no requiere cuenta. Solo se necesita el link.

**¿Cómo cambio mi contraseña?**
Por ahora, usa "¿Olvidaste tu contraseña?" en la pantalla de inicio de sesión.