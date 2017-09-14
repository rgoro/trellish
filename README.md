# Trellish

Esta es una API para manejo de una lista de tareas.  Cuenta con opearciones para crear, mantener y listar el listado.  Se implementó en PHP, usando el microframework *Slim*, usando *MongoDB* como base de datos y *Memcached*  para agilizar los listados.

La API está expuesta, a modo de *demo*, en [URL]

## Interfaz de la API
### Modelo de datos
Las tareas se guardan en documentos que siguen el siguiente esquema:

```javascript
{
	"_id"         : ObjectID,    // Automático
	"title"       : string,      // Obligatorio
	"due_date"    : mixed,       // Obligatorio, ver detalles más abajo
	"due_time"    : UTCDateTime, // Generado
	"completed"   : bool,        // Automático
	"created_at"  : UTCDateTime, // Automático
	"updated_at"  : UTCDateTime, // Automático
	"description" : string       // Opcional
}
```

El campo `due_date` contiene la fecha y hora tal y como se envió en la creación de la tarea.  Puede venir como *UNIX timestamp* (segundos desde 1/1/1970) o en el formato aceptado por la función PHP [strtotime](http://php.net/strtotime).  Los calculos de comparaciones, orden etc. deberían hacerse sobre el campo `due_time`, que es la versión "computable" de dicho campo.

### *Endpoints* de la API
Todos los métodos expuestos devuelven, en caso de error, un JSON con el siguiente formato:
```javascript
{
	"error_message" : string,
	"data_received" : Object // Data envíada, si corresponde.
}
```

* [/create](URL) -- POST para crear una tarea.  Espera un JSON en el que los dos campos obligatorios deben estar presentes y los automáticos se ignoran. Puede responder:
  + HTTP 200 y el JSON de la tarea completa si la operación es exitosa
  + HTTP 400 si falta alguno de los parámetros obligatorios o si `due_date` está en un formato inválido
  + HTTP 500 si se produce alguna excepción en la operación.
* [/task{id}](URL) -- GET para mostrar una tarea.  Puede responder
  + HTTP 200 y el JSON correspondiente si se encuentra.
  + HTTP 404 si no existe.
  + HTTP 500 si se produce algún error.
* [/update/{id}](URL) -- PUT para modificar una tarea. Espera un JSON similar con los campos a modificar (se asume que los ausentes no se modifican).  Puede responder:
  + HTTP 200 y el JSON de la tarea completa si la operación es exitosa
  + HTTP 404 si no existe la tarea que se intentó modificar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/mark_complete/{id}](URL) y [/mark_incomplete/{id}](URL) -- PUT para unicamente marcar como completa o incompleta una tarea.  Puede responder
  + HTTP 200 si la operación es exitosa.
  + HTTP 404 si no existe la tarea que se intentó modificar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/delete/{id}](URL) DELETE para eliminar una tarea.  Puede responder 
  + HTTP 200 si la operación es exitosa.
  + HTTP 404 si no existe la tarea que se intentó eliminar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/tasks](URL) GET para listar tareas según ciertos filtros descriptos más abajo.  Puede responder
  + HTTP 200 y un JSON con el array de tareas correspondientes.
  + HTTP 400 si alguno de los parámetros de fechas es inválido.
  + HTTP 500 si se produce alguna excepción en la operación.

#### Formato de los filtros
Para filtrar el listado se usa una *queryString* que puede tener los siguientes parámetros:
* `duedate_from / duedate_to`: Intervalo inclusivo de fechas en que la tarea debe estar lista, con el mismo formato que para la creación.
* `created_from / created_to`: Intervalo inclusivo de fechas en que la tarea fue creada, con el mismo formato.
* `updated_from / updated_to`: Intervalo inclusivo de fechas en que la tarea fue modificada por última vez, con el mismo formato.
* `onlycomplete`: Con cualquier valor, mostrar sólo las tareas completas.
* `onlyincomplete`: Con cualquier valor, mostrar sólo las tareas incompletas.  Se ignora si está presente `onlycomplete`.
* `page`: Los resultados se devuelven de a 5, este parámetro indica qué página mostrar.  La primer página lleva el nro 0.

Para todos los intervalos puede estar uno solo de los dos extremos.  No se verifica que, de estar ambos, se genere un intervalo válido, es decir, que el límite sea posterior al inicio.

## Instrucciones de instalación o deploy

## Observaciones sobre la implementación
### Framework elegido

### Uso de MongoDB y Memcached

