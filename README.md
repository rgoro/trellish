# Trellish

This is a web service representing a TODO list.  It has operations to create, mantain and list tasks.  It's iplemented using PHP, with the microframework *Slim*, using *MongoDB* as database and *Memcached* to cachè the lists.

The Web service is deployed, as a demo, in a server on *Amazon EC2* (http://ec2-54-175-156-36.compute-1.amazonaws.com/tasks)


## Service API
### Data model
The tasks are stored as documents using the following schema:

```javascript
{
	"_id"         : ObjectID,    // Automatic
	"title"       : string,      // Required
	"due_date"    : mixed,       // Required, see details below
	"due_time"    : UTCDateTime, // Generated
	"completed"   : bool,        // Automatic
	"created_at"  : UTCDateTime, // Automatic
	"updated_at"  : UTCDateTime, // Automatic
	"description" : string       // Optional
}
```

The field `due_date` holds the date and/or time exactly as it was sent on the creation of the task.  It could be either an UNIX timestamp (seconds since 1-1-1970) or the format accepted by the PHP function [strtotime](http://php.net/strtotime).  All data operations like comparisons, sorting, etc., should be run over the field `due_time`, which holds the same data in a format easily undestood by the database engine.  Notice that this field, being implemented with Mongo's DateTime (UTCDateTime or ISODate), has millisecond precision, while the entry formats have only second precision.


### Endpoints
All API methods return, if there was an error, a JSON with this format::
```javascript
{
	"error_message" : string,
	"data_received" : Object // Data sent, if appropiate.
}
```

* [/create](http://ec2-54-175-156-36.compute-1.amazonaws.com/create) -- POST to create a task.  The request should have a JSON with both mandatory fields.  The automatic ones are ignored.  Possible responses:
  + HTTP 200 and the task's full JSON, if the operation was succesful.
  + HTTP 400 if any of the mandatory parameters is missing or if the data in the field `due_date` is in an ivalid format.
  + HTTP 500 if there was any exception during the operation.
* [/task/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/task/{id}) -- GET to show a single task. Possible responses:
  + HTTP 200 and the task's JSON if it's found.
  + HTTP 404 if there's no task with such id.
  + HTTP 500 if there was any exception during the operation.
* [/update/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/update/{id}) -- PUT to update a task.  The request should have a JSON similar to `/create`, with the fields to be modified (those absent are not modified).  Possible responses:
  + HTTP 200 and the task's full JSON, if the operation was succesful.
  + HTTP 404 if there's no task with such id.
  + HTTP 500 if there was any exception during the operation.
* [/mark_complete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/mark_complete/{id}) y [/mark_incomplete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/mark_incomplete/{id}) -- PUT to just mark a task as complete or incomplete.  Possible responses
  + HTTP 200 if the operation is succesful.
  + HTTP 404 if there's no task with such id. 
  + HTTP 500 if there was any exception during the operation.
* [/delete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/delete/{id}) DELETE to remove a task.  Possible responses 
  + HTTP 200 if the operation is succesful.
  + HTTP 404 if there's no task with such id.
  + HTTP 500 if there was any exception during the operation.
* [/tasks](http://ec2-54-175-156-36.compute-1.amazonaws.com/tasks) GET to list tasks according to certain criteria, described below.  Possible responses
  + HTTP 200 and a JSON with an array of tasks if the operation is succesful.
  + HTTP 400 if any of the filter parameters (the dates) is invalid.
  + HTTP 500 if there was any exception during the operation.

#### Filter format
To filter the list of tasks, a *queryString* is used.  It may contain any of these parameters:
* `duedate_from / duedate_to`: Inclusive interval of due dates for the tasks, with the same format as in the creation endpoint.
* `created_from / created_to`: Inclusive interval of creation dates for the tasks, with the same format as in the creation endpoint.
* `updated_from / updated_to`: Inclusive interval of the last modification dates for the tasks, with the same format as in the creation endpoint.
* `onlycomplete`: With any value, shows only the completed tasks.
* `onlyincomplete`: With any value, shows only the incomplete tasks.  It's ignored if `onlycomplete` is present.
* `page`: The results are returned in pages of 5 tasks.  This parameter defines which page to show, starting on 0.

For all intervals, any of the extremes may be absent.  There is no validation of the intervals defined, i.e., if both from and to are present, the service doesn't check if the latter is more recent than the former. If no parameter is present, the first five tasks created will be returned.

## Instalation or deploy instructions.
### Dependencies
The Web Service was developed using:
* Ubuntu 17.04
* PHP 7.0
* Apache 2.4 (con mod-rewrite activado)
* MongoDB 3.2
* Memcached 1.4

The test deploy server is instantiated with Ubuntu 16.04, which hs the same verions of these packages, with the exception of MongoDB, which must be installed following (these instructions)[https://docs.mongodb.com/v3.2/tutorial/install-mongodb-on-ubuntu/#install-mongodb-community-edition]

The packages `php-memcahed` and `php-mongodb` must be installed.  For the latter one the version required is 1.2 or newer, which is not default on Ubuntu 16.04 where PECL must be used to get said version. For the deployment `git` and `composer` must be also installed.  Notice that the latter has it's own dependencies that should be added to the PHP environment.

The deploy operation consists on cloning the repository and then downloading the dependencies using composer:

```
$ git clone https://github.com/rgoro/trellish.git
$ composer update
```

## Implementation details
### Choice of framework
Due to the small scale of the project, a microframework was considered the best option, since the only functionality needed was routing.  [Slim](https://www.slimframework.com/) was selected due to it having been used before on a project of similar scale, although the expertise was for an earlier major version, which added a few difficulties on the start of the project.  This framework doesn't offer any MVC functionality, nor an ORM.  In fact, no entity Task class was implemented, only a small controller for the router to delegate to.  The framework does offer some dependency injection, which was used.

Por tratarse de un proyecto sencillo se eligió usar un microframework que brindara la mínima funcionalidad necesaria, en este caso, el routeo.  Se optó por [Slim](https://www.slimframework.com/), dado que ya se lo había usado para un proyecto de escala similar.  Este framework no brinda ni un andamiaje de MVC ni un ORM, y de hecho no se implementó una clase que represente la entidad *Task*.  Sólo se agregó un pequeño *controller* para resolver las tareas requeridas.

### Use of MongoDB and Memcached
The schema specified was used to store the tasks in a collection in the database, with the sole exception of duplicating the due date to "as sent by the frontend" (`due_date`) and a machine readable format (`due_time`).

As for the caching of task lists, they were stored fully on memecahed, using a hash of the *queryString* as a key, and store them with a TTL of 60 seconds.  The hash used is SHA1, but any other could have been used.  Buth the choice of key and TTL are aribarry, no research on good caching practices was made.

---

# Trellish

Este es un *Web Service* para manejo de una lista de tareas.  Cuenta con operaciones para crear, mantener y listar dichas tareas.  Se implementó en PHP, usando el microframework *Slim*, usando *MongoDB* como base de datos y *Memcached*  para agilizar los listados.

La API está expuesta, a modo de *demo*, en un servidor de test en *Amazon EC2* (http://ec2-54-175-156-36.compute-1.amazonaws.com/tasks)

## Interfaz del servicio
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

El campo `due_date` contiene la fecha y hora tal y como se envió en la creación de la tarea.  Puede venir como *UNIX timestamp* (segundos desde 1/1/1970) o en el formato aceptado por la función PHP [strtotime](http://php.net/strtotime).  Los calculos de comparaciones, orden etc. deberían hacerse sobre el campo `due_time`, que es la versión "computable" de dicho campo.  Nótese que este campo, por estar implementado con el DateTime de Mongo (UTCDateTime o ISODate), tiene precisión de milisegundos, mientras que los formatos de entrada tienen precisión de segundos.

### *Endpoints* de la API
Todos los métodos expuestos devuelven, en caso de error, un JSON con el siguiente formato:
```javascript
{
	"error_message" : string,
	"data_received" : Object // Data envíada, si corresponde.
}
```

* [/create](http://ec2-54-175-156-36.compute-1.amazonaws.com/create) -- POST para crear una tarea.  Espera un JSON en el que los dos campos obligatorios deben estar presentes y los automáticos se ignoran. Puede responder:
  + HTTP 200 y el JSON de la tarea completa si la operación es exitosa
  + HTTP 400 si falta alguno de los parámetros obligatorios o si `due_date` está en un formato inválido
  + HTTP 500 si se produce alguna excepción en la operación.
* [/task/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/task/{id}) -- GET para mostrar una tarea.  Puede responder
  + HTTP 200 y el JSON correspondiente si se encuentra.
  + HTTP 404 si no existe.
  + HTTP 500 si se produce algún error.
* [/update/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/update/{id}) -- PUT para modificar una tarea. Espera un JSON similar al de `/create` con los campos a modificar (los ausentes no se modifican).  Puede responder:
  + HTTP 200 y el JSON de la tarea completa si la operación es exitosa
  + HTTP 404 si no existe la tarea que se intentó modificar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/mark_complete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/mark_complete/{id}) y [/mark_incomplete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/mark_incomplete/{id}) -- PUT para unicamente marcar como completa o incompleta una tarea.  Puede responder
  + HTTP 200 si la operación es exitosa.
  + HTTP 404 si no existe la tarea que se intentó modificar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/delete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/delete/{id}) DELETE para eliminar una tarea.  Puede responder 
  + HTTP 200 si la operación es exitosa.
  + HTTP 404 si no existe la tarea que se intentó eliminar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/tasks](http://ec2-54-175-156-36.compute-1.amazonaws.com/tasks) GET para listar tareas según ciertos filtros descriptos más abajo.  Puede responder
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

Para todos los intervalos puede estar uno solo de los dos extremos.  No se verifica que, de estar ambos, se genere un intervalo válido, es decir, que el límite sea posterior al inicio.  De no haber ningún parámetro, el sistema devolverá las primeras cinco tareas creadas.

## Instrucciones de instalación o deploy
### Dependencias
El sistema está desarrollado sobre:
* Ubuntu 17.04
* PHP 7.0
* Apache 2.4 (con mod-rewrite activado)
* MongoDB 3.2
* Memcached 1.4

El deploy de prueba está instanciado con Ubuntu 16.04, que tiene las mismas versiones de estos paquetes, a excepción de Mongo, para lo que hay que instalar la versión actualizada siguiendo (estas instrucciones)[https://docs.mongodb.com/v3.2/tutorial/install-mongodb-on-ubuntu/#install-mongodb-community-edition]

Sobre la instalación *default* de PHP debe agregarse los paquetes `php-memcached` y `php-mongodb`, este último en versión 1.2 o superior (en Ubuntu 16.04 hay que instalar dicha versión usando `pecl`).  Para el deploy también deben estar instalados `git` y `composer`.  Nótese que este último puede tiene dependencias propias sobre la instalación de PHP.

El deploy consiste en clonar el repositorio y luego bajar las dependencias usando composer:

```
$ git clone https://github.com/rgoro/trellish.git
$ composer update
```

## Observaciones sobre la implementación
### Framework elegido
Por tratarse de un proyecto sencillo se eligió usar un microframework que brindara la mínima funcionalidad necesaria, en este caso, el routeo.  Se optó por [Slim](https://www.slimframework.com/), dado que ya se lo había usado para un proyecto de escala similar, aunque la experiencia fue con una versión anterior, lo que agregó algunas dificultades al inicio del proyecto.  Este framework no brinda ni un andamiaje de MVC ni un ORM, y de hecho no se implementó una clase que represente la entidad *Task*.  Sólo se agregó un pequeño *controller* para resolver las tareas requeridas.  El *framework* si provee una funcionalidad de inyección de dependencias, que se aprovechó.

### Uso de MongoDB y Memcached
Se respetó el esquema pedido para guardar las tareas en una collección en la base de datos, con la exepción de guardar tanto la fecha límite tal y como viene "del frontend" como en un formato que le resulta más sencillo de trabajar a la base de datos.

En cuanto al cacheo de los listados, se decidió usar como clave un *hash* de la *queryString*, y darle un TTL de 60 segundos.  Se optó por un hash SHA1, aunque podría haberse usado cualquier otro (MD4 y 5, SHA256, etc.).  Tanto la elección de qué clave usar y del TTL son arbitrarios, no se investigó en prácticas recomendadas para la implementación de cacheo.
