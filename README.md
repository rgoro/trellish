# Trellish

Esta es una API para manejo de una lista de tareas.  Cuenta con opearciones para crear, mantener y listar el listado.  Se implementó en PHP, usando el microframework *Slim*, usando *MongoDB* como base de datos y *Memcached*  para agilizar los listados.

La API está expuesta, a modo de *demo*, en un servidor de test en *Amazon EC2* (http://ec2-54-175-156-36.compute-1.amazonaws.com/tasks)

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
* [/update/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/update/{id}) -- PUT para modificar una tarea. Espera un JSON similar con los campos a modificar (se asume que los ausentes no se modifican).  Puede responder:
  + HTTP 200 y el JSON de la tarea completa si la operación es exitosa
  + HTTP 404 si no existe la tarea que se intentó modificar.
  + HTTP 500 si se produce alguna excepción en la operación.
* [/mark_complete/{id}](http://ec2-54-175-156-36.compute-1.amazonaws.com/mark_complete/{id}) y [/mark_incomplete/{id}](URL) -- PUT para unicamente marcar como completa o incompleta una tarea.  Puede responder
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

El deploy de prueba está instanciado con Ubuntu 16.04 que tiene las mismas versiones de estos paquetes, a excepción de Mongo, para lo que hay que instalar la versión actualizada siguiendo (estas instrucciones)[https://docs.mongodb.com/v3.2/tutorial/install-mongodb-on-ubuntu/#install-mongodb-community-edition]

Sobre la instalación *default* de PHP debe agregarse los paquetes `php-memcached` y `php-mongodb`, este último en versión 1.2 o superior (en Ubuntu 16.04 hay que instalar dicha versión usando `pecl`).  Para el deploy también deben estar instalados `git` y `composer`.  Nótese que este último puede tiene dependencias propias sobre la instalación de PHP.

El deploy consiste en clonar el repositorio y luego bajar las dependencias usando composer:

```
$ git clone https://github.com/rgoro/trellish.git
$ composer update
```

## Observaciones sobre la implementación
### Framework elegido
Por tratarse de un proyecto sencillo se eligió usar un microframework que brindara la mínima funcionalidad necesaria, en este caso, el routeo.  Se optó por [Slim](https://www.slimframework.com/), dado que ya se lo había usado para un proyecto de escala similar.  Este framework no brinda ni un andamiaje de MVC ni un ORM, y de hecho no se implementó una clase que represente la entidad *Task*.  Sólo se agregó un pequeño *controller* para resolver las tareas requeridas.

### Uso de MongoDB y Memcached
Se respetó el esquema pedido para guardar las tareas en una collección en la base de datos, con la exepción de guardar tanto la fecha límite tal y como viene "del frontend" como en un formato que le resulta más sencillo de trabajar a la base de datos.

En cuanto al cacheo de los listados, se decidió usar como clave un *hash* de la *queryString*, y darle un TTL de 60 segundos.  Se optó por un hash SHA1, aunque podría haberse usado cualquier otro (MD4 y 5, SHA256, etc.).  Tanto la elección de qué clave usar y del TTL son arbitrarios, no se investigó en prácticas recomendadas para la implementación de cacheo.

