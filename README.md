# Lark Framework

Lark is a modern, lightweight app framework designed specifically for developing REST APIs.

- [Installation](#installation)
- [Routing](#routing)
  - [Routes](#routes)
  - [Route Parameters](#route-parameters)
  - [Route Actions](#route-actions)
  - [Middleware](#middleware)
- [Logging](#logging)
- [Exception Handling](#exception-handling)
- [Debugger](#debugger)
- [Configuration & Bindings](#configuration--bindings)
- [Environment Variables & Configuration](#environment-variables--configuration)
- [Request](#request)
- [Response](#response)
- [Database](#database)
- [Schema](#schema)
- [Model](#model)
- [Validator](#validator)
  - [Validation Types & Rules](#validation-types--rules)
- [Filter](#filter)
- [Entity](#entity)
- [HTTP Client](#http-client)
- [CLI](#cli)
- [File](#file)
- [Timer](#timer)
- [Helpers](#helpers)
  - [`app()`](#helper-app), [`db()`](#helper-db), [`dbdatetime()`](#helper-dbdatetime), [`debug()`](#helper-debug), [`env()`](#helper-env), [`f()`](#helper-f), [`halt()`](#helper-halt), [`logger()`](#helper-logger), [`p()`](#helper-p), [`pa()`](#helper-pa), [`req()`](#helper-req), [`res()`](#helper-res), [`router()`](#helper-router), [`x()`](#helper-x)

## Installation

Requirements:

- PHP 8
- PHP extensions
  - Required
  - [...]
  - Optional
  - [curl](https://www.php.net/manual/en/book.curl.php) - if using `Lark\Http\Client`

#### Composer Install

```
composer require lark/framework
```

## Routing

The router is used to dispatch route actions and middleware.

```php
// bootstrap
// ...

// define routes
router()
    // get([route], [action])
    ->get('/', function() {});

// run app
app()->run();
```

### Routes

There are multiple ways to define routes.

```php
// routes for HTTP specific methods:
router()->delete('/route', function(){});
router()->get('/route', function(){});
router()->head('/route', function(){});
router()->options('/route', function(){});
router()->patch('/route', function(){});
router()->post('/route', function(){});
router()->put('/route', function(){});

// route for all HTTP methods
router()->all('/route', function(){});

// route for multiple HTTP methods
router()->route(['GET', 'POST'], '/route', function(){});

// a wildcard route "*" can be used to match any route
router()->get('*', function(){}); // all HTTP GET methods
router()->all('*', function(){}); // all HTTP methods (all requests)
router()->route(['GET', 'POST'], '*', function(){}); // all HTTP GET and POST methods
```

#### Regular Expression Routes

Regular expression routes use [PCRE](https://www.php.net/manual/en/book.pcre.php) patterns for matching routes.

```php
// match all routes that begin with "/api"
router()->get('/api.*?', function(){});
```

#### Route Groups

Route groups can be used to simplify defining similar routes.

```php
router()
    ->group('/api/users') // group([base-route])
    ->get('/', function(){}) // "/api/users"
    ->get('/active', function(){}); // "/api/users/active"
```

#### Route Group Loading

Route groups can be defined in _route files_ which are loaded during routing (lazy load routes).

```php
// bootstrap routes directory
// ...

router()->load([
    // [base-route] => [file]
    '/api/users' => 'users'
]);

// in routes directory file "users.php" defines routes
// the group('/api/users') method does not need to be called (handled by load() method)
router()
    ->get('/', function(){}) // "/api/users"
    ->get('/active', function(){}); // "/api/users/active"
```

Inside route files `router()` should only be called once to avoid false route no match errors.

```php
// incorrect:
router()->bind(function(){});
router()->get('/', function(){});
// correct:
router()
    ->bind(function(){})
    ->get('/', function(){});
```

#### Route Controller

A route controller object can be used with [Route Groups](#route-groups) and [Route Group Loading](#route-group-loading).

```php
class MyController implements Lark\Router\RouteControllerInterface
{
    public function bind(Router $router): void
    {
        $router->get('/users', function(){}); // "/api/users"
    }
}

// in routes file
router()
    ->group('/api')
    ->controller(new MyController);
```

### Route Actions

Route actions are executed when a route is matched. Route actions can be a callable function (`Closure`) or array with `[class, method]`. The first route matched is the only route action that will be executed.

```php
// function will be called on route match
router()->get('/example-html', function(): string {
    return 'hello'; // return string to output as html
});

router()->get('/example-json', function(): array {
    return ['message' => 'hello']; // return array|stdClass to output as JSON
    // will auto add header "Content-Type: application/json"
    // and response body will be:
    // {"message": "hello"}
});

// class method "App\Controller\ExampleController::hello()" will be called on route match
router()->get('/example2', [App\Controller\ExampleController::class, 'hello']);
```

### Route Not Found Action

If no route match is found a not found action can be defined. The HTTP response status code is auto set to `404`.

```php
router()->notFound(function(string $requestMethod, string $requestPath){});
```

If a not found action is not defined a `Lark\Router\NotFoundException` will be thrown.

### Route Parameters

#### Named Parameters

Route named parameters are required parameters that do not use regular expressions. Multiple name parameters are allowed.

```php
router()->get('/users/{id}', function($id){});
```

#### Optional Named Parameters

Route optional named parameters are optional parameters that do not use regular expressions. Optional named parameters can only be used at the end of the route. Multiple optional named parameters are allowed.

```php
router()->get('/users/{id}/{groupId?}', function($id, $groupId = null){});
```

In this example the `groupId` parameter is optional, so route `/users/5` and `/users/5/10` would both match.

#### Regular Expression Parameters

Regular expressions can be used to define parameters using [PCRE](https://www.php.net/manual/en/book.pcre.php) patterns. Multiple regular expression parameters are allowed.

```php
// match digits
router()->get('/users/(\d+)', function(int $id){});
// or match alphanumeric with length of 8
router()->get('/users/([a-z0-9]{8})', function(string $id) {});
```

### Middleware

Middleware is a single or multiple actions that are executed before a route action is called. Middleware actions can be executed always or only when a route is matched. Middleware must be defined _before_ routes are defined. Middleware actions follow the same structure as [Route Actions](#route-actions). The arguments `Lark\Request $req` and `Lark\Response $res` are passed to all middleware actions.

```php
// executed always
router()->bind(function(Lark\Request $req, Lark\Response $res){});
// executed if any route is matched
router()->matched(function(Lark\Request $req, Lark\Response $res){});

// define routes
// ...
```

Multiple middleware actions can be set.

```php
// single action
router()->bind(function(){});
// multiple actions
router()->bind(function(){}, [MyClass::class, 'myMethod']);
// array of actions
router()->bind([
    function(){},
    function(){}
]);
```

#### Route Middleware

Route specific middleware actions are only executed if the route is matched.

```php
// method([methods], [route], [...actions])
router()->map(['GET'], '/api.*?', function(){});

router()->get('/api/users', function(){});
```

If the HTTP request is `/api/users` then both the middleware action and route action would be executed.

#### Middleware Execution Order

Middleware is always executed in the following order:

1. Always execute (`router()->bind(...)`)
2. Execute mapped on matched route (`router()->map(...)`)
3. Execute on matched route (`router()->matched(...)`)
4. After middleware (`router()->after(...)`)

#### Route Group Middleware

Middleware can be defined to be used only on a specific route group. Route group middleware actions are only executed if a group route is matched.

```php
router()
    // group([base-route], [...actions])
    ->group('/api/users', function(){})
    ->get('/', function(){}) // "/api/users"
    ->get('/{id}', function($id){}) // "/api/users/{id}"
```

#### After Middleware

After middlware always runs after a route action has been called, even if the route does not exist.

```php
router()->after(function(){}, [MyClass::class, 'myMethod']);
```

## Logging

`Lark\Logger` is used for logging. The helper function [`logger()`](#helper-logger) is available.

```php
logger('channel')->critical('message', [context]);
logger('channel')->debug('message', [context]);
logger('channel')->error('message', [context]);
logger('channel')->info('message', [context]);
logger('channel')->warning('message', [context]);
```

Logging info level record example.

```php
// bootstrap log handler
app()->logHandler = new App\LogHandler;
Lark\Logger::handler(app()->logHandler);

// ...

// log info level record
logger('user')->info('User has been authorized', ['userId' => $user->id]);

// ...

// output log example
print_r( app()->logHandler->close() );
```

Global context can be added to all context sent in log record.

```php
Lark\Logger::globalContext(['sessionId' => $session->id]);
// ...
logger('user')->info('User has signed out', ['userId' => $user->id]);
// context is: ['sessionId' => x, 'userId' => y]
```

## Exception Handling

Exceptions can be handled using the exception handler.

```php
// bootstrap
// ...

// define routes
// ...

try
{
    // run app
    app()->run();
}
catch (Throwable $th)
{
    new App\ExceptionHandler($th);
}
```

Example `App\ExceptionHandler` class.

```php
namespace App;
use Throwable;
class ExceptionHandler
{
    public function __construct(Throwable $th)
    {
        \Lark\Exception::handle($th, function (array $info) use ($th)
        {
            $code = $th->getCode();
            if (!$code)
            {
                $code = 500;
            }

            // log error
            // ...

            // respond with error
            res()
                ->code($code)
                ->json($info);

            // --or-- continue to throw exception
            throw $th;
        });
    }
}
```

## Debugger

`Lark\Debugger` can be used for debugging. The helper functions [`debug()`](#helper-debug) and [`x()`](#helper-x) are available.

```php
use Lark\Debugger;

// append debugger info
Debugger::append(['some' => 'info'])
    ->name('Test info') // this will be displayed as title (optional)
    ->group('test'); // this will group info together (optional)

Debugger::append(['more' => 'info'])
    ->name('More test info')
    ->group('test');

Debugger::dump(); // dump all debugger info and exit
// or use:
// x(); // dump all debugger info and exit
```

## Configuration & Bindings

Framework configuration and bindings can be set with the `use()` method.

### Debugging

Enable Lark internal append debugger info for debugger dump.

```php
app()->use('debug.dump', true);
```

Enable Lark internal debug logging.

```php
app()->use('debug.log', true);
```

### Database Connections

Database connections are registered using the syntax `db.connection.[connectionId]` and accessed using the syntax `[connectionId]$[database]$[collection]`.

```php
// setup default MongoDB database connection with connectionId "default"
// the first registered connection is always the default connection
// regardless of connectionId
app()->use('db.connection.default', [
    'hosts' => ['127.0.0.1'],
    'username' => 'test',
    'password' => 'secret',
    'replicaSet' => 'rsNameHere', // (optional)
    // options can override any global database options
    // (optional, see "Database Global Options" below)
    'options' => []
]);

// register second connection with connectionId "myconn"
app()->use('db.connection.myconn', [...]);

// ...

// use default connection (no connectionId required):
$db = db('dbName$collectionName');
// or: $db = db('dbName', 'collectionName');

// use non-default connection (connectionId required):
$db2 = db('myconn$dbName$collectionName');
// or: $db = db('myConn2', 'dbName', 'collectionName');
```

> Read more in [Database](#database) and helper function [`db()`](#helper-db).

### Database Global Options

Database global options can be set using `db.options`. All default option values are listed below.

```php
use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\WriteConcern;

app()->use('db.options', [
    'db.allow' => [], // allow access to only specific databases
    'db.deny' => ['admin', 'config', 'local'], // restrict access to databases
    'debug.dump' => false, // will include all database calls/context in debugger dumper
    'debug.log' => false, // add debug level database messages to logger
    'find.limit' => 1_000, // find "limit" for find options
    'read.concern' => new ReadConcern, // MongoDB read concern
    'write.concern' => new WriteConcern(WriteConcern::MAJORITY) // MongoDB write concern
]);
```

> Read more about Write Concern in the [MongoDB docs](https://www.mongodb.com/docs/manual/reference/write-concern/) and in the [PHP docs](https://www.php.net/manual/en/class.mongodb-driver-writeconcern.php).

### Database Sessions

Sessions can be stored in the database using a `Lark\Model` object.

```php
app()->use('db.session', new App\Model\Session);
```

### Validator Custom Rules

Custom validator rules can be registered using `validator.rule.[type].[ruleClassName]`.

```php
app()->use('validator.rule.string.beginWithEndWith', App\Validator\BeginWithEndWith::class);
```

## Environment Variables & Configuration

`Lark\Env` is used for app environment variables and configuration. The helper function [`env()`](#helper-env) is available.

Example read `PATH` environment variable.

```php
$path = env('PATH');

// or use default value "/my/path" if environment variable does not exist
$path2 = env('PATH2', '/my/path');

// for required environment vars do not use a default value argument
// which will throw exception if the environment var does not exist
$path2 = env('PATH2');
// Lark\Exception exception thrown: Invalid env variable key "PATH2"
```

Example `.env` file.

```
DB_USER=myuser
DB_PWD=secret
```

Example `.env` file usage.

```php
// load from file (bootstrap)
Lark\Env::getInstance()->load(DIR_ROOT . '/.env');

$dbUser = env('DB_USER'); // myuser
$dbPassword = env('DB_PWD'); // secret
```

Other `Lark\Env` methods: `fromArray(array $array)`, `has(string $key): bool` and `toArray(): array`.

## Request

`Lark\Request` provides HTTP request data with input sanitizing. The helper function [`req()`](#helper-req) is available.

```php
// example request:
// POST /example
// Content-Type: application/json
// {"name": "Test", "contact": {"email": "test@example.com"}}
$data = req()->json(); // get all as object/array (no auto sanitizing)

// request JSON must be an array or 400 response is sent
$data = req()->jsonArray();
// request JSON must be an object or 400 response is sent
$data = req()->jsonObject();
```

> If HTTP header `Content-Type: application/json` does not exist for any JSON methods, an automatic response with HTTP status code `400` and JSON body `{"message": "Invalid JSON: [reason]"}` will be sent.

Individual JSON fields can also be accessed with sanitizing.

```php
// get individual field
$name = req()->jsonField('name')->string();
if(req()->jsonField('contact.email')->has())
{
    $email = req()->jsonField('contact.email')->email();
}
```

`POST` request ( `Content-Type: application/x-www-form-urlencoded` ) example.

```php
if(req()->isMethod('POST'))
{
    $name = req()->input('name')->string();
    if(req()->input('email')->has())
    {
        $email = req()->input('email')->email();
    }
}
```

`GET` request example.

```php
// request "/?id=5&name=Shay"
print_r([
    'id' => req()->query('id')->integer(),
    // use "default" as value if query "name" does not exist
    'name' => req()->query('name', 'default')->string()
]); // Array ( [id] => 5 [name] => Shay )
```

Request cookie example.

```php
if(req()->cookie('myCookie')->has())
{
    var_dump( req()->cookie('myCookie')->string() );
}
```

### Request Session

`Lark\Request\Session` is used to manage sessions.

```php
app()->session->set('user.id', 5); // creates session data: [user => [id => 5]]
// ...
if(app()->session->has('user.id'))
{
    $userId = app()->session->get('user.id');
}
```

> Sessions can be stored in the database by using `Lark\Database\Session::handler()`.

`Lark\Request\SessionFlash` can be used to store short-term data where the data is available from when set through the following request, example:

```php
app()->session()->flash()->set('userError', 'Invalid session');
// redirect, then use message
echo app()->session()->flash()->get('userError');
// message is no longer available on next request
```

### Request Methods

- `body(bool $convertHtmlEntities = true): string` - request raw body data getter
- `contentType(): string` - content-type getter
- `cookie(string $key, $default = null): Lark\Request\Cookie` - cookie input object getter
- `hasHeader(string $key): bool` - check if header key exists
- `header(string $key): string` - header value getter
- `headers(): array` - get all headers
- `host(): string` - HTTP host value getter, like `www.example.com`
- `input(string $key, $default = null): Lark\Request\Input` - input object getter for `POST`
- `ipAddress(): string` - IP address getter
- `isContentType(string $contentType): bool` - validate request content-type
- `isMethod(string $method): bool` - validate request method
- `isSecure(): bool` - check if request is secure (HTTPS)
- `json()` - JSON request body getter
- `jsonArray(): array` - JSON request body getter, must be array or `400` HTTP status code response
- `jsonField(string $field, $default = null): Lark\Request\Json` - JSON request field object getter
- `jsonObject(): array` - JSON request body getter, must be object or `400` HTTP status code response
- `method(): string` - request method getter
- `path(): string` - path getter, like `/the/path`
- `pathWithQueryString(): string` - path with query string getter, like `/the/path?x=1`
- `port(): int` - port getter
- `query(string $key, $default = null): Lark\Request\Query` - query input object getter for `GET`
- `queryString(): string` - query string getter, like `x=1&y=2`
- `scheme(): string` - URI scheme getter, like `http`
- `session(): Lark\Request\Session` - session object getter
- `uri(): string` - URI getter, like `http://example.com/example?key=x`

### Request Input Methods

Input methods include methods for request input objects: `Cookie`, `Input` and `Query`.

- `email(array $options = [])` - value getter, sanitize as email
- `float(array $options = ['flags' => FILTER_FLAG_ALLOW_FRACTION])` - value getter, sanitize as float
- `has(): bool` - check if key exists
- `integer(array $options = [])` - value getter, sanitize as integer
- `string(array $options = [])` - value getter, sanitize as string
- `url(array $options = [])` - value getter, sanitize as URL

### Session Methods

Session methods `clear()`, `get()`, `has()` and `set()` all use dot notation for keys, for example: `set('user.isActive', 1) equals: [user => [isActive => 1]]`.

- `clear(string $key)` - clear a key
- `static cookieOptions(array $options)` - set cookie options
  - default options are: `['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false]`
- `destroy()` - destroy a session
- `static exists(): bool` - check if sessions are enabled and session exists
- `get(string $key)` - value getter
- `has(string $key): bool` - check if key exists
- `id(): ?string` - session ID getter
- `isSession(): bool` - check if session exists
- `set(string $key, $value)` - key/value setter
- `toArray(): array` - session array getter

## Response

`Lark\Response` is used to control the HTTP response.

```php
// set header, status code 200, content-type and send JSON response
res()
    ->header('X-Test', 'value')
    ->code(Lark\Response::HTTP_OK)
    ->contentType('application/json') // not required when using json()
    ->json(['ok' => true]);
// {"ok": true}
```

### Response Methods

- `cacheOff(): Lark\Response` - disable cache using cache-control
- `contentType(string $contentType): Lark\Response` - content-type setter
- `cookie($key, $value, $expires, $path, $domain, $secure, $httpOnly): bool` - cookie setter
- `cookieClear(string $key, string $path = '/'): bool` - remove cookie
- `header(string $key, $value): Lark\Response` - header setter
- `headerClear(string $key): Lark\Response` - remove header key
- `headers(array $headers): Lark\Response` - headers setter using array
- `json($data)` - respond with JSON payload (and content-type `application/json` in headers)
- `redirect(string $location, bool $statusCode301 = false)` - send redirect
- `send($data)` - respond with raw data payload
- `code(int $code): Lark\Response` - response status code setter

# Database

`Lark\Database` is used to access MongoDB database and collection instances. The helper function [`db()`](#helper-db) is available.

```php
// bootstrap
// setup default MongoDB database connection with connectionId "default"
app()->use('db.connection.default', [...]);

// register second connection with connectionId "myconn"
app()->use('db.connection.myconn', [...]);

// ...

// get Database object instance
$db = db('myDb$users');
```

#### Insert Documents

```php
// insert documents
$docIds = $db->insert([
    ['name' => 'Test', 'role' => 'admin'],
    ['name' => 'Test2', 'role' => 'admin']
]);
// Array ( [0] => 62ba4fd034faaf6fc132ef54 [1] => 62ba4fd034faaf6fc132ef55 )

// insert single document
$docId = $db->insertOne(['name' => 'Test3', 'role' => 'readonly']);
```

#### Find Documents

```php
// find documents
$docs = $db->find(['role' => 'admin']);
// Array ( [0] => Array ( [id] => 62ba4fd034faaf6fc132ef54 [name] => Test [role] => admin )
// [1] => Array ( [id] => 62ba4fd034faaf6fc132ef55 [name] => Test2 [role] => admin ) )

// find documents with "name" staring with "Test"
$docs = $db->find(['name' => ['$regex' => '^Test']]);

// find documents by IDs
$docs = $db->findIds(['62ba4fd034faaf6fc132ef54', '62ba4fd034faaf6fc132ef55']);

// find single document
$doc = $db->findOne(['name' => 'Test2']);

// find single document by ID
$doc = $db->findId('62ba4fd034faaf6fc132ef54');
```

#### Update Documents

```php
// update documents
$affected = $db->update(['role' => 'admin'], ['role' => 'admin2']);

// update bulk
$docIds = $db->updateBulk([
    ['id' => '62ba4fd034faaf6fc132ef55', 'role' => 'admin'],
    [...]
]);
// Array ( [0] => 62ba4fd034faaf6fc132ef55 [1] => ... )

// update single document by ID
$newDoc = $db->updateId('62ba4fd034faaf6fc132ef55', ['role' => 'admin2']);

// update single document
$newDoc = $db->updateOne(['name' => 'Test2'], ['role' => 'admin']);
```

By default update methods used the `$set` operator for updates, like `['$set' => ['role' => 'admin]]`. This operator can be changed, for example:

```php
// increment visits by 1
$newDoc = $db->updateOne(['name' => 'Test2'], ['visits' => 1], operator: '$inc');
```

#### Replace Documents

```php
// replace bulk
$docIds = $db->replaceBulk([
    ['id' => '62ba4fd034faaf6fc132ef55', 'name' => 'Test222'],
    [...]
]);
// Array ( [0] => 62ba4fd034faaf6fc132ef55 [1] => ... )

// replace single document by ID
$newDoc = $db->replaceId('62ba4fd034faaf6fc132ef55',
    ['name' => 'Test2222', 'role' => 'admin']);

// replace single document
$newDoc = $db->replaceOne(['name' => 'Test2222'], ['name' => 'Test2', 'role' => 'admin']);
```

#### Delete Documents

```php
// delete documents (note: filter cannot be empty)
$affected = $db->delete(['role' => 'admin']);

// delete documents by IDs
$affected = $db->deleteIds(['62ba4fd034faaf6fc132ef54', '62ba4fd034faaf6fc132ef55']);

// delete single document
$affected = $db->deleteOne(['name' => 'Test2']);

// delete all documents in collection
$affected = $db->deleteAll();
```

#### Collection Field Methods

```php
// create a new field
// set default value to empty array
$affected = $db->collectionField('tags')->create([]);

// delete a field
$affected = $db->collectionField('tags')->delete();

// check if a field exists on all documents
$exists = $db->collectionField('tags')->exists();

// check if a field exists on any document
$exists = $db->collectionField('tags')->exists(false);

// remove value "mytag" from field "tags" array
$affected = $db->collectionField('tags')->pull(
    ['id' => '62ba4fd034faaf6fc132ef54'],
    'mytag'
);

// append values "mytag1" and "mytag2" to field "tags" array
// these values will only be appended if they
// don't already exists in the array
// use $unique=false to always append
$affected = $db->collectionField('tags')->push(
    ['id' => '62ba4fd034faaf6fc132ef54'],
    ['mytag1', 'mytag2']
);

// rename a field
$affected = $db->collectionField('tags')->rename('tagsNew');
```

> Use dot notation for nested field names like `field1.field2`.

### Database Methods

- `collectionField(string $field): Database\Field` - collection field object getter
- `count(array $filter = [], array $options = []): int` - count documents matching filter
- `delete(array $filter, array $options = []): int` - delete documents matching filter
- `deleteAll(array $options = []): int` - delete all documents
- `deleteIds(array $ids, array $options = []): int` - delete documents by ID
- `deleteOne(array $filter, array $options = []): int` - delete single document matching filter
- `drop(): bool` - drop collection
- `exists(): bool` - check if collection exists
- `find(array $filter = [], array $options = []): array` - find documents matching filter
- `findId($id, array $options = []): ?array` - find document by ID
- `findIds(array $ids, array $options = []): array` - find documents by ID
- `findOne(array $filter = [], array $options = []): ?array` - find single document matching filter
- `has(array $filter, array $options = []): bool` - check if documents matching filter exist
- `hasIds(array $ids, array $options = []): bool` - check if documents with IDs exist
- `insert(array $documents, array $options = []): array` - insert documents
- `insertOne($document, array $options = []): ?string` - insert single document
- `ping(): bool` - ping command
- `replaceBulk(array $documents, array $options = []): int` - bulk replace
- `replaceId($id, $document, array $options = []): int` - replace single document
- `replaceOne(array $filter, $document, array $options = []): int` - replace single document
- `update(array $filter, $update, array $options = []): int` - update documents matching filter
- `updateBulk(array $documents, array $options = []): int` - bulk update
- `updateId($id, $update, array $options = []): int` - update document by ID
- `updateOne(array $filter, $update, array $options = []): int` - update single document matching filter

#### Database Field Methods

- `create($defaultValue = null): int` - create field with default value
- `delete(): int` - delete collection field
- `exists(bool $allDocs = true): bool` - check if field exists or checks if field exists on any document if `!$allDocs`
- `pull(array $filter, $value): int` - remove value from field array
- `push(array $filter, $value, $unique = true): int` - append value to field array, if `$unique` will only append value if doesn't already exist in field array
- `rename(string $newName): int` - rename field

## Schema

`Lark\Schema` is used to create schemas for creating entities, entity validation and database collection creation.

```php
use Lark\Schema;
$schema = new Schema([
    // create an index when creating a database collection
    '$index' => [
        'name' => 1, 'age' => 1, '$name' => 'idxNameAge'
    ],
    // or create multiple indexes
    // '$indexes' => [
    //    ['username' => 1, '$name' => 'idxUsername', '$unique' => true],
    //    ['name' => 1, 'age' => 1, '$name' => 'idxNameAge']
    // ],

    // auto database projection (filter password by default)
    '$filter' => ['password' => 0],

    // schema fields
    'name' => ['string', 'notEmpty'],
    'username' => ['string', 'notEmpty'],
    'password' => ['string', 'notEmpty'],
    'age' => ['int', 'notEmpty'],
    'isAdmin' => ['bool', 'notNull', ['default' => false]]
]);
```

Schema uses [Validation Types & Rules](#validation-types--rules) for field definitions.

> Options for in `$index` and `$indexes` are any field starting with `$`, like `$unique`, and more options can be found in the [MongoDB docs](https://www.mongodb.com/docs/manual/reference/method/db.collection.createIndex/#options).

Default field values can also be set dynamically. For nested fields use dot notation like `field.nestedfield`.

```php
$schema->default('isAdmin', false);
```

Field value callbacks can be used. For nested fields use dot notation like `field.nestedfield`.

```php
$schema->apply('name', function($name): string {
    return strtoupper($name);
});
```

### Field Schema Imports

A schema file can be imported as a schema for a schema field. First, create a partial schema in a schema file, for example: `[DIR_SCHEMAS]/partials/users.info.php`.

```php
<?php
return [
    'object',
    [
        'fields' => [
            'age' => 'int',
            'tags' => 'array'
        ]
    ]
];
```

Next, add the import using a field name and file.

```php
$schema = new Schema([
    '$import' => [
        // field => file (in schemas directory)
        'info' => 'partials/users.info'
    ],
    'name' => ['string', 'notEmpty'],
    // field for schema import (optional, does not need to be set here)
    'info' => null
]);
```

Printing `$schema->toArray()` will output:

```
Array
(
    [name] => Array
        (
            [0] => string
            [1] => notEmpty
        )

    [info] => Array
        (
            [0] => object
            [1] => Array
                (
                    [fields] => Array
                        (
                            [age] => int
                            [tags] => array
                        )

                )

        )

)
```

Nested fields (using dot notation) can also be used.

```php
$schema = new Schema([
    '$import' => [
        // field => file (in schemas directory)
        'info.1.fields' => 'partials/users.info'
    ],
    'name' => ['string', 'notEmpty'],
    'info' => [
        'object',
        ['fields' => null]
    ]
]);
```

Example partial schema in: `[DIR_SCHEMAS]/partials/users.info.php`:

```php
<?php
return [
    'age' => 'int',
    'tags' => 'array'
];
```

## Model

`Lark\Model` is a model: a way to simplify database calls and creating/validating entities.

```php
namespace App\Model;
use App\Model;
use Lark\Schema;

class User extends Model
{
    const DBS = 'default$app$users';
    public static function &schema(): Schema
    {
        return parent::schema([
            'name' => ['string', 'notEmpty'],
            'age' => ['int', 'notEmpty'],
            'isAdmin' => ['bool', 'notNull', ['default' => false]]
        ]);
    }
}
```

The `App\Model\User` class can be used for creating an entity and validation.

```php
$user = (new App\Model\User)->make([
    'name' => 'Bob',
    'age' => 25
]);
var_dump($user);
// array(3) { ["name"]=> string(3) "Bob" ["age"]=> int(25) ["isAdmin"]=> bool(false) }

// or an array can be used
$user = (new App\Model\User)->makeArray([
    ['name' => 'Bob', 'age' => 25],
    ['name' => 'Jane', 'age' => 21]
]);
```

The `$mode` argument can be used to change the validator mode, for example requiring document IDs with `replace+id` or `update+id`:

```php
// schema: ['id' => ['string', 'id'], 'name' => ['string', 'notEmpty']]
$user = (new App\Model\User)->make([
    'name' => 'Bob'
], 'update+id');
// throws Lark\Validator\ValidatorException:
// Validation failed: "User.id" must be a string
```

The `$mode` argument can be used to allow missing fields that can be used for partial documents with `update` or `update+id`:

```php
$user = (new App\Model\User)->make([
    'name' => 'Bob'
], 'update');
var_dump($user); // array(1) { ["name"]=> string(3) "Bob" }
```

The `Model::db()` method can be used to access the Model database collection (`Model::DBS` must be set).

```php
// ...
class Model extends Model
{
    const DBS = 'default$app$users';
    public function get(string $id): ?array
    {
        return $this->db()->findId($id);
    }
}

// get user document
$user = (new App\Model\User)->get('62ba4fd034faaf6fc132ef55');

// external calls: get documents
$docs = (new \App\Model\User)->db()->find(['role' => 'admin']);
```

> Important: Model classes shouldn't have any required parameters in their `Model::__construct()` method, because the Models are automatically instantiated when using model/database binding, and any required parameters will not be present.

### Model Schema Method

The `Model::schema()` method can be used in multiple ways. By default the method will use the `Model::SCHEMA` schema file constant to load the schema from file.

Another way to create a schema is overriding the parent method and passing the schema array:

```php
class ExampleModel extends Model
{
    public static function &schema(): Schema
    {
        return parent::schema([
            'id' => ['string', 'id'],
            // ...
        ]);
    }
}
```

The above method caches the schema object, so when the schema method is called again it returns the referenced `Schema` object.

A callback can also be passed to access the `Schema` object created by the parent method, example:

```php
class ExampleModel extends Model
{
    const SCHEMA = 'users.php';
    public static function &schema(): Schema
    {
        return parent::schema(function(Schema &$schema)
        {
            $schema->apply('name', function($name)
            {
                return strtoupper($name);
            });
        });
    }
}
```

### Model Database Query

The model `Lark\Database\Query` class can be used for input query parameters.

```php
use Lark\Database\Query;
use App\Model\User;

$query = new Query(new User, [
    'name' => 'test'
]);

// Database::find()
$results = $query->find();

// Database::count()
$count = $query->count();
```

#### Query Selectors

Query selectors can be used as query parameters. Match a field with field value:

```php
$query = [
    'name' => 'test'
];
```

[MongoDB comparison selectors](https://www.mongodb.com/docs/manual/reference/operator/query/#comparison) `$eq`, `$gt`, `$gte`, `$in`, `$lt`, `$lte`, `$ne` and `$nin` can be used like:

```php
$query = [
    'age' => ['$gte' => 18]
];
```

With the `$in` selector:

```php
$query = [
    'name' => ['$in' => ['test', 'test2', 'test3']]
];
```

With multiple selectors:

```php
$query = [
    'age' => ['$gt' => 20, '$lt' => 100]
];
```

#### Query Options

By default queries with multiple selectors will perform a logical `AND` operation. A logical `OR` operation can be used with the `$or` option:

```php
$query = [
    // age is greater than 20 OR less than 100
    'age' => ['$gt' => 20, '$lt' => 100],
    '$or' => true
];
```

The `$filter` (or `$projection`) option can be used to filter the document fields returned from the database:

```php
$query = [
    // only include fields "id" and "name" for each document
    '$filter' => ['id' => 1, 'name' => 1],
    'name' => 'test',
    'age' => ['$gte' => 18]
];

// or fields can be excluded for each document
$query = [
    // exclude fields "age" and "info" for each document
    '$filter' => ['age' => 0, 'info' => 0]
];
```

The `$page` option can be used for pagination.

```php
// fetch first page
$query = [
    '$page' => 1
];

// fetch second page
$query = [
    '$page' => 2
];
```

> By default the limit of documents per page is determined by the database option `find.limit`.

> The default sort order of documents for the `$page` option is `["id" => 1]`, this can be overridden using the `$sort` option.

The `$limit` option can be used to set the number of documents returned or to override the default documents per page when using the `$page` option.

```php
$query = [
    '$limit' => 100
];
```

> The `$limit` option value cannot exceed the database option `find.limit` value.

The `$sort` option can be used to set the sort order or documents.

```php
// sort by "name" ASC and "age" DESC
$query = [
    '$sort' => ['name' => 1, 'age' => -1]
];
```

The `$skip` option can be used to set the query skip value.

```php
$query = [
    '$skip' => 10
];
```

> The `$skip` option will always be overridden when used with the `$page` option.

### Auto Created and Updated Field Values

Created and updated field values can be used to auto set fields with created and updated date/times. Example schema:

```php
[
    '$created' => 'createdAt',
    '$updated' => 'updatedAt',
    'name' => ['string', 'notNull'],
    'createdAt' => ['timestamp', 'notNull'],
    'updatedAt' => ['timestamp', 'notNull']
]
```

Now the `createdAt` and `updatedAt` fields with be auto set to the current timestamp (`time()`). The values can be set to `timestamp` by default, or can be set to `datetime` for `DateTime` or `dbdatetime` for `MongoDB\BSON\UTCDateTime`, example:

```php
[
    '$created' => [
        'createdAt' => 'dbdatetime'
    ],
    // ...
]
```

In the above examples the `createdAt` field will be set once (using schema default value) and the `updatedAt` field will be set each time the document is made.

### Database Model Schema Constraints

Database model schema constraints can be used as database constraints on references like verifying foreign keys and deleting documents by references.

#### Refs Foreign Key Constraint

The `$refs.fk` constraint verifies foreign keys, can be set in any model schema and is used with the `Database` methods: `insert()`, `insertOne()`, `replaceBulk()`, `replaceId()`, `replaceOne()`, `update()`, `updateBulk()`, `updateId()` and `updateOne()`.

```php
class UserLog extends Model
{
    const DBS = 'default$app$users.log';
    public static function &schema(): Schema
    {
        return parent::schema([
            '$refs' => [
                'fk' => [
                    // collection => [localField => foreignField, ...]
                    'users' => ['userId' => 'id']
                ]
            ],
            'id' => ['string', 'id'],
            'userId' => ['string', 'notEmpty'],
            'message' => ['string', 'notEmpty']
        ]);
    }
}
```

Example document in `users.log`:

```
{
    "id": "abc",
    "userId": "62ba4fd034faaf6fc132ef54",
    "message": "test"
}
```

Now when a model database insert/replace/update method is called the `$refs.fk` constraint above will verify the collection `users.log` field `userId` value exists as a foreign key in the `users` collection field `id` (`_id`).

> If foreign key constraint verification fails a `Lark\Database\Constraint\DatabaseConstraintException` exception will be thrown with a message like `Failed to insert or update document(s), foreign key constraint failed for "userId"`.

> The `$refs.fk` foreign fields (`foreignField`) must always be a MongoDB `ObjectId` and foreign key verification on any other type will fail.

> The `$refs.fk` constraint will always verify a foreign key, even when the local field value is `null`, but this can be disabled by using the `nullable$` prefix on the local field name, like `nullable$userId`, which means all local field null values will not have the foreign key verified.

The `$refs.fk` constraint can also be used on an array of foreign keys in an array:

```php
// class UserGroup (model)
$schema = new Schema([
    '$refs' => [
        'fk' => [
            // collection => [localField => foreignField, ...]
            'users' => ['users.$' => 'id']
        ]
    ],
    // ...
]);
```

Example document in `users.groups`:

```
{
    "id": "abc",
    "name": "group name",
    "users": ["62ba4fd034faaf6fc132ef54", "62ba4fd034faaf6fc132ef55"]
}
```

Now when a model database insert/replace/update method is called the `$refs.fk` constraint above will verify each value in the collection `users.groups` field `users` array exists as a foreign key in the `users` collection field `id` (`_id`).

The `$refs.fk` constraint can also be used on objects in an array that have foreign keys:

```php
// class UserAllowed (model)
$schema = new Schema([
    '$refs' => [
        'fk' => [
            // collection => [localField => foreignField, ...]
            'users' => ['users.$.id' => 'id']
        ]
    ],
    // ...
]);
```

Example document in `users.allowed`

```
{
    "id": "abc",
    "role": "admin"
    "users": [
        {"id": "62ba4fd034faaf6fc132ef54", "name": "test"}
        {"id": "62ba4fd034faaf6fc132ef55", "name": "test2"}
    ]
}
```

Now when a model database insert/replace/update method is called the `$refs.fk` constraint above will verify the collection `users.allowed` field `users` array to ensure each object field `id` value exists as a foreign key in the `users` collection field `id` (`_id`).

The `$refs.fk` constraint can be used with multiple collections and fields:

```php
$schema = new Schema([
    '$refs' => [
        'fk' => [
            // collection => [localField => foreignField, ...]
            'users' => [
                'userId' => 'id',
                'users.$' => 'id',
                'usersAllowed.$.id' => 'id'
            ]
        ]
    ],
    // ...
]);
```

The `$refs.fk` constraint can also be used with the same model:

```php
// class User (model)
$schema = new Schema([
    '$refs' => [
        'fk' => [
            // allow managerId to be null (no manager)
            // verify FK users.id exists when users.managerId exists
            'users' => ['nullable$managerId' => 'id']
        ]
    ],
    'id' => ['string', 'id'],
    'managerId' => 'string'
]);
```

#### Refs Clear Constraint

The `$refs.clear` constraint allows clearing field values, can be set in any model schema and is used with the `Database::deleteIds()` method.

```php
class User extends Model
{
    const DBS = 'default$app$users';
    public static function &schema(): Schema
    {
        return parent::schema([
            '$refs' => [
                'clear' => [
                    // collection => [foreign fields]
                    'users.log' => ['userId']
                ]
            ],
            'id' => ['string', 'id'],
            'name' => ['string', 'notEmpty']
        ]);
    }
}
```

Example document in `users.log`:

```
{
    "id": "abc",
    "userId": "62ba4fd034faaf6fc132ef54",
    "message": "test"
}
```

Now when the model database method `deleteIds()` is called the `$refs.clear` constraint above will trigger a database clear (update operation) to clear all document `userId` fields in the `users.log` collection with `userId: {$in: [ids]}`.

> The equivalent in MongoDB shell would be:
>
> ```
> db.users.delete( { _id: { $in: [ids] } } )
> db.users.log.updateMany( { userId: { $in: [ids] } }, { $set: { userId: null } } )
> ```

#### Refs Delete Constraint

The `$refs.delete` constraint allows deleting documents, can be set in any model schema and is used with the `Database::deleteIds()` method.

```php
class User extends Model
{
    const DBS = 'default$app$users';
    public static function &schema(): Schema
    {
        return parent::schema([
            '$refs' => [
                'delete' => [
                    // collection => [foreign fields]
                    'users.log' => ['userId']
                ]
            ],
            'id' => ['string', 'id'],
            'name' => ['string', 'notEmpty']
        ]);
    }
}
```

Example document in `users.log`:

```
{
    "id": "abc",
    "userId": "62ba4fd034faaf6fc132ef54",
    "message": "test"
}
```

Now when the model database method `deleteIds()` is called the `$refs.delete` constraint above will trigger a database delete operation to delete all documents in the `users.log` collection with `userId: {$in: [ids]}`.

> The equivalent in MongoDB shell would be:
>
> ```
> db.users.delete( { _id: { $in: [ids] } } )
> db.users.log.delete( { userId: { $in: [ids] } } )
> ```

The `$refs.delete` constraint can also be used to pull (`$pullAll`) IDs from an array:

```php
$schema = new Schema([
    '$refs' => [
        'delete' => [
            // collection => [foreign fields]
            'users.groups' => ['users.$']
        ]
    ],
    // ...
]);
```

Example document in `users.groups`:

```
{
    "id": "abc",
    "name": "group name",
    "users": ["62ba4fd034faaf6fc132ef54", "62ba4fd034faaf6fc132ef55"]
}
```

Now when the model database method `deleteIds()` is called the `$refs.delete` constraint above will trigger a database update operation to `$pullAll` IDs in the collection `users.groups` field `users`.

> The equivalent in MongoDB shell would be:
>
> ```
> db.users.delete( { _id: { $in: [ids] } } )
> db.users.groups.updateMany(
>     { users: { $in: [ids] } },
>     { $pullAll: { users: [ids] } },
>     { multi:true }
> )
> ```
>
> Note: even when multiple values are pulled from an array on a single document field MongoDB will still return `modifiedCount: 1`

The `$refs.delete` constraint can also be used to pull (`$pull`) objects from an array based on an object field value:

```php
$schema = new Schema([
    '$refs' => [
        'delete' => [
            // collection => [foreign fields]
            'users.allowed' => ['users.$.id']
        ]
    ],
    // ...
]);
```

Example document in `users.allowed`

```
{
    "id": "abc",
    "role": "admin"
    "users": [
        {"id": "62ba4fd034faaf6fc132ef54", "name": "test"}
        {"id": "62ba4fd034faaf6fc132ef55", "name": "test2"}
    ]
}
```

Now when the model database method `deleteIds()` is called the `$refs.delete` constraint above will trigger a database update operation to `$pull` all objects in collection `users.groups` field `users` based on object field `id` value.

> The equivalent in MongoDB shell would be:
>
> ```
> db.users.delete( { _id: { $in: [ids] } } )
> db.users.allowed.updateMany(
>     { users.id: { $in: [ids] } },
>     { $pull: { users: { id: { $in: [ids] } } } },
>     { multi:true }
> )
> ```
>
> Note: even when multiple objects are pulled from an array on a single document field MongoDB will still return `modifiedCount: 1`

The `$refs.delete` constraint can be used with multiple collections and fields:

```php
$schema = new Schema([
    '$refs' => [
        'delete' => [
            // collection => [foreign fields]
            'users.log' => ['userId', 'userId2'],
            'users.groups' => ['users.$'],
            'users.allowed' => ['users.$.id']
        ]
    ],
    // ...
]);
```

## Validator

`Lark\Validator` is used for validation and making entities.

```php
use Lark\Validator;

$isValid = (new Validator([
    // data
    'name' => 'Bob',
    'age' => 25
], [
    // schema
    'name' => ['string', 'notEmpty'],
    'age' => ['int', 'notNull'],
    'phone' => null, // no type (any type allowed), optional
    'title' => 'string' // string, optional
]))->validate(); // true
```

Assertion can be used during validation.

```php
(new Validator([
    'name' => null
], [
    'name' => ['string', 'notNull']
]))->assert();
// throws Lark\Validator\ValidatorException:
// Validation failed: "name" must be a string
```

Make entities with validation.

```php
// validation will pass because no field is required
var_dump(
    (new Validator([], [
        'name' => ['string'],
        'age' => ['int']
    ]))->make()
);
// array(2) { ["name"]=> NULL ["age"]=> NULL }
```

### Validation Types & Rules

Rules `notNull` and `notEmpty`, and sometimes `id`, are rules for all types that do not allow the value to be `null`.
The rule `voidable` can be used for any fields that can be missing.

- any type (default) - any type allowed
  - `notNull` - value cannot be `null`
- `array` (or `arr`) - value can be `array` or `null`
  - `allowed` - array values must be allowed `[allowed => [...]]`
  - `length` - number of array items must be `[length => x]`
  - `max` - array values cannot exceed maximum value of `[max => x]`
  - `min` - array values cannot be lower than minimum value of `[min => x]`
  - `notEmpty` - must be a non-empty `array`
  - `notNull` - must be an `array`
  - `unique` - array values must be unique
- `boolean` (or `bool`) - must be `boolean` or `null`
  - `notNull` - must be `boolean`
- `datetime` - must be an instance of `DateTime` or `null`
  - `notNull` - must be instance of `DateTime`
- `dbdatetime` - must be an instance of `MongoDB\BSON\UTCDateTime` or `null`
  - `notNull` - must be instance of `MongoDB\BSON\UTCDateTime`
- `float` - must be a `float` or `null`
  - `between` - must be between both values `[between => [x, y]]`
  - `max` - must be a maximum value of `[max => x]`
  - `min` - must be a minimum value of `[min => x]`
  - `notEmpty` - must be a `float` greater than zero
  - `notNull` - must be a `float`
- `integer` (or `int`) - must be an `integer` or `null`
  - `between` - must be between both values `[between => [x, y]]`
  - `id` - must be an `integer` when `ENTITY_FLAG_ID` flag is set
  - `max` - must be a maximum value of `[max => x]`
  - `min` - must be a minimum value of `[min => x]`
  - `notEmpty` - must be an `integer` greater than zero
  - `notNull` - must be an `integer`
- `number` (or `num`) - must be a number or `null`
  - `between` - must be between both values `[between => [x, y]]`
  - `id` - must be a number when `ENTITY_FLAG_ID` flag is set
  - `max` - must be a maximum value of `[max => x]`
  - `min` - must be a minimum value of `[min => x]`
  - `notEmpty` - must be a number greater than zero
  - `notNull` - must be a number
- `object` (or `obj`) - must be an `object` or `null`
  - `notEmpty` - must be a non-empty `object`
  - `notNull` - must be an `object`
- `string` (or `str`) - must be a `string` or `null`
  - `allowed` - value must be allowed `[allowed => [...]]`
  - `alnum` - must only contain alphanumeric characters
  - or, must only contain alphanumeric characters and whitespaces `[alnum => true]`
  - `alpha` - must only contain alphabetic characters
  - or, must only contain alphabetic characters and whitespaces `[alpha => true]`
  - `contains` - must contain value `[contains => x]`
  - or, must contain value (case-insensitive) `[contains => [x, true]]`
  - `email` - must be a valid email address
  - `hash` - hashes must be equal (timing attack safe) `[hash => x]`
  - `id` - must be an `string` when `ENTITY_FLAG_ID` flag is set
  - `ipv4` - must be valid IPv4 address
  - `ipv6` - must be valid IPv6 address
  - `json` - must be a valid JSON
  - `length` - length must be number of characters `[length => x]`
  - `match` - value must be a regular expression match `[match => x]`
  - `max` - length must be a maximum number of characters `[max => x]`
  - `min` - length must be a minimum number of characters `[min => x]`
  - `notAllowed` - value must be allowed `[notAllowed => [...]]`
  - `notEmpty` - must be a non-empty `string`
  - `notNull` - must be a `string`
  - `password` - passwords must match `[password => x]`
  - `url` - must be a valid URL
- `timestamp` - must be a timestamp or `null`
  - `notNull` - must be a timestamp

### Nested Fields

Nested fields can be defined using the `fields` property.

```php
$isValid = (new Validator([
    // data
    'name' => 'Bob',
    'contact' => [
        'email' => 'bob@example.com',
        'phone' => [
            'cell' => '555-5555',
            'office' => '555-6666'
        ]
    ]
], [
    // schema
    'name' => ['string', 'notEmpty'],
    'contact' => [
        'array',
        [
            'fields' => [
                'email' => ['string', 'email'],
                'phone' => [
                    'array',
                    [
                        'fields' => [
                            'cell' => 'string',
                            'office' => 'string'
                        ]
                    ]
                ]
            ]
        ]
    ]
]))->validate(); // true
```

### Nested Schemas

Nested schemas can be defined for an array of arrays or objects using the `schema:array` or `schema:object` property.

```php
$isValid = (new Validator([
    'name' => 'test',
    'tags' => [
        // these must be arrays because "schema:array" is used
        // if "schema:object" is used these must be objects
        ['id' => '1', 'name' => 'test2'],
        ['id' => 2, 'name' => 'test3'],
    ]
], [
    'name' => ['string', 'notEmpty'],
    'tags' => [
        'array', 'notEmpty',
        [
            'schema:array' => [
                'id' => ['int', 'notNull'],
                'name' => 'string'
            ]
        ]
    ]
]))->assert();
// throws Lark\Validator\ValidatorException:
// Validation failed: "tags.0.id" must be an integer or null
```

> In the example above if the schema rule `notEmpty` is not used before the `schema:array` or `schema:object` property, and the array of arrays or objects is empty, no rules will be validated/asserted.

> Partial documents are not allowed inside nested schema objects or arrays.

### Assert Callback

A callback can be used with the `assert()` method.

```php
(new Validator([
    'name' => null
], [
    'name' => ['string', 'notNull']
]))->assert(function(string $field, string $message, string $name = null){
    // handle error
    //...

    // return true to halt
    // return false to continue to throw validation exception
    return true;
});
```

### Custom Validation Rule

Custom validation rules can be created.

```php
// validator.rule.[type].[name]
app()->use('validator.rule.string.beginWithEndWith', App\Validator\BeginWithEndWith::class);

// App\Validator\MyRule class:
namespace App\Validator;
class BeginWithEndWith extends \Lark\Validator\Rule
{
    private string $beginWith;
    private string $endWith;

    protected string $message = 'must begin with value and end with value';

    public function __construct(string $beginWith, string $endWith)
    {
        $this->beginWith = $beginWith;
        $this->endWith = $endWith;
    }

    public function validate($value): bool
    {
        $beginsWith = substr($value, 0, strlen($this->beginWith));
        $endsWith = substr($value, -(strlen($this->endWith)));

        return $beginsWith === $this->beginWith && $endsWith === $this->endWith;
    }
}

// validation example
(new Validator([
    'alias' => '123testXYZ'
], [
    'alias' => ['string', ['beginWithEndWith' => ['123', 'XYZ']]]
]))->validate(); // true
```

It is also possible to override existing rules.

```php
// validator.rule.[type].[name]
// overwrite existing string rule "email"
app()->use('validator.rule.string.email', 'App\\Validator\\Email');

// App\Validator\Email class:
namespace App\Validator;
class Email extends \Lark\Validator\TypeString\Email
{
    public function validate($value): bool
    {
        // must be valid email and domain "example.com"
        return parent::validate($value)
            && preg_match('/@example\.com$/i', $value) === 1;
    }
}

// validation example
(new Validator([
    'email' => 'test@example.com'
], [
    'email' => ['string', 'email']
]))->validate(); // true
```

## Filter

`Lark\Filter` is used for filtering values.

```php
$cleanStr = filter()->string($str);
```

Filter by array keys.

```php
$arr = ["one" => 1, "two" => 2, "three" => 3];

// exclude filter
print_r(
    filter()->keys($arr, ["two" => 0])
); // Array ( [one] => 1 [three] => 3 )

// include filter
print_r(
    filter()->keys($arr, ["one" => 1, "two" => 1])
); // Array ( [one] => 1 [two] => 2 )
```

### Filter Methods

- `email($value, array $options = []): string` - sanitize value with email filter
- `float($value, array $options = ['flags' => FILTER_FLAG_ALLOW_FRACTION]): float` - sanitize value with float filter
- `integer($value, array $options = []): int` - sanitize value with integer filter
- `keys(array $array, array $filter): array` - filters keys based on include or exclude filter
- `string($value, array $options = ['flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH]): string` - sanitize value with string filter
- `url($value, array $options = []): string` - sanitize value with url filter

## Entity

`Lark\Factory\Entity` is used for mapping class properties to array and array to class properties.

```php
use Lark\Factory\Entity;

// class must be subclass of Entity
class Location extends Entity
{
    public string $address;
    public string $city;
}

class User extends Entity
{
    // properties must be public and typed
    // union types are not supported
    public string $name;
    public int $age;
    public bool $isActive = false; // default values
    public Location $location; // deep nested classes supported
}

// populate from array
$user = new User([
    'name' => 'Bob',
    'age' => 25,
    'location' => [
        'address' => '101 main',
        'city' => 'Tampa'
    ]
]);
// or use: $user->fromArray([...])

echo $user->name; // Bob
echo $user->location->address; // 101 main

// get as array
$userArr = $user->toArray(); // [name => Bob, ...]
```

## HTTP Client

`Lark\Http\Client` is an HTTP client.

```php
use Lark\Http\Client;
$client = new Client;
try
{
    $res = $client->get('http://example.com');
    $headers = $client->headers();
    $statusCode = $client->statusCode();

    if($statusCode === 200)
    {
        // ok
    }
    else
    {
        // handle
    }
}
catch (Lark\Http\HttpException $ex)
{
    // handle request/curl error
}
```

Various HTTP methods are available.

```php
// DELETE request
$client->delete('http://example.com', ['field1' => 'value']);
// GET request
$client->get('http://example.com', ['param' => 1]); // http://example.com?param=1
// HEAD request
$client->head('http://example.com');
// OPTIONS request
$client->options('http://example.com');
// PATCH request
$client->patch('http://example.com', ['field1' => 'value']);
// POST request
$client->post('http://example.com', ['field1' => 'value']);
// PUT request
$client->put('http://example.com', ['field1' => 'value']);
```

Strings can also be used to send JSON.

```php
$client = new Client([
    'headers' => ['content-type' => 'application/json']
]);
// POST request with JSON string
$client->post('http://example.com', json_encode(['field1' => 'value']));
```

Options can be set for all methods (will override default options).

```php
use Lark\Http\Client;
$client = new Client(['url' => 'http://example.com', 'timeout' => 8]);
$res = $client->get('/api/items'); // http://example.com/api/items
$res2 = $client->post('/api/items', ['name' => 'My Item']);
```

Options can be set for individual methods (will override default options and options for all methods).

```php
$res = $client->get('/api/items', ['timeout' => 5]);
```

Options for `curl` can be set.

```php
use Lark\Http\Client;
$client = new Client([
    'curl' => [
        CURLOPT_RESOLVE => ['test.loc:127.0.0.1']
    ]
]);
```

### HTTP Client Options

- `curl` - set options for `curl` using `CURLOPT_[...]` options
- `headers` - set HTTP headers, which can be set using two methods
  - `['headers' => ['My-Header' => 'value']]`
  - `['headers' => ['My-Header: value']]`
- `port` - set a custom port number
- `proxy` - use an HTTP proxy
- `redirects` - allow redirects
- `timeout` - timeout in seconds for connection and execution
- `url` - base URL for request methods
- `verify` - verify peer's certificate and common name

## CLI

`Lark\Cli` is used to create CLI apps.

```php
// bootstrap
// ...

$cli = Lark\Cli::getInstance();

// add command
$cli->command('files', 'Print files in directory')
    ->arg('dir', 'Read directory')
    ->action(function(string $dir) {
        // print files in directory $dir

        // optional, exit with any code by returning an int
        // return 1; // same as $cli->exit(1);
    });
    // or use class/method:
    // ->action([MyClass::class, 'methodName'])

// run app
$cli->run($_SERVER['argv']);
```

Arguments and options can be set for a command, and each argument and option has optional settings.

```php
// set global option (separate from command options)
$cli->option('-d, --debug', 'Enable debug mode', function() {
    // enable here
});

$cli->command('files', 'Print files in directory')
    ->arg('dir', 'Read directory') // required by default
    // set another optional argument that can have multiple values (array)
    ->arg('subdirs', 'Read subdirectories', ['optional', 'array'])
    // add option for output file
    ->option('-o, --outputfile', 'Output to file')
    // option test
    ->option('-t, --test', 'Run test', ['optional'])
    // add command action
    ->action(function(string $dir, ?array $subdirs, ?string $outputfile, ?bool $isTest) {
        var_dump($dir, $subdirs, $outputfile, $isTest);
    });

// $ php ./app/cli.php files mydir subdir1 subdir2 --outputfile=/my/file -t
// string(5) "mydir"
// array(2) { [0] => string(7) "subdir1" [1] => string(7) "subdir2" }
// string(8) "/my/file"
// bool(true)
```

The CLI `Lark\Cli\Output` class is used for output and styling output.

```php
$o = $cli->output();

// output green text
$o->colorGreen->echo('This is green text');
// use multiple styles
$o->colorBlue->styleUnderline->echo('More text');

// style methods for common styles
$o->error('Error'); // red background
$o->info('Info'); // blue text
$o->ok('Success'); // green text
$o->warn('Warning'); // yellow text
$o->dim('Muted'); // dim text

// custom style methods can be registered
$o::register('blink', function ($text, $end = PHP_EOL) use ($out) {
    $out->styleBlink;
    $out->echo($text, $end);
});
$o->bink('Blinking text'); // blinking text

// override existing style methods
$o::register('error', function ($text, $end = PHP_EOL) use ($out) {
    $out->colorRed; // text color red (instead of bg red)
    $out->stderr($text, $end); // send to stderr
});
$o->error('Oops'); // red text
```

The output `grid()` method can be used to evenly space columns.

```php
$data = [
    [1, "one"],
    [2, "two"],
    [100, "one hundred"],
    [3, "three"],
];

$out->grid($data, ['indent' => 2]);
```

Above example would output:

```
  1      one
  2      two
  100    one hundred
  3      three
```

Use `confirm()` for prompting.

```php
// "Continue? (y/N)"
if($cli->confirm("Continue?")) // ...
// or yes by default: "Continue? (Y/n)"
if($cli->confirm("Continue?", true)) // ...
```

Use `input()` for input.

```php
// "Enter value [DEFAULT]:"
$val = $cli->input("Enter value:", "DEFAULT");
// if no value is entered the value would be "DEFAULT"
```

### CLI Methods

- `abort($status = 0)` - display command aborted message and exit app
- `command(string $name, string $description, array $aliases = []): Command` - register a command
- `confirm(string $question, bool $isDefaultYes = false)` - confirm yes/no
- `exit($status = 0)` - exit app
- `header(callable $callback)` - register a header callback used int `help()` method
- `help()` - display help (auto invoked by `Cli`)
- `input(string $text, $default = null)` - input
- `option(string $option, string $description, callable $action)` - set global option
- `output()` - CLI `Output` object getter
- `run()` - run CLI app

### CLI Command Methods

- `action($callbackOrClassArray): Command` - set command action
- `arg(string $arg, string $description, array $options = []): Command` - set argument
  - Options:
    - `array` - argument with multiple values (must be last in arguments list)
    - `default` - default value, like: `['default' => 'the value']`
    - `optional` - argument is optional
- `option(string $option, string $description = '', array $options = []): Command` -set option
  - Options:
    - `default` - default value, like: `['default' => 'the value']`

### CLI Output Propeties

- `bgBlack` - style background black
- `bgBlue` - style background blue
- `bgCyan` - style background cyan
- `bgGray` - style background gray
- `bgGreen` - style background green
- `bgPurple` - style background purple
- `bgRed` - style background red
- `bgWhite` - style background white
- `bgYellow` - style background yellow
- `bgLigthBlue` - style background light blue
- `bgLightCyan` - style background light cyan
- `bgLightGray` - style background light gray
- `bgLightGreen` - style background light green
- `bgLightPurple` - style background light purple
- `bgLightRed` - style background light red
- `bgLightYellow` - style background light yellow
- `colorBlack` - style color black
- `colorBlue` - style color blue
- `colorCyan` - style color cyan
- `colorGray` - style color gray
- `colorGreen` - style color green
- `colorPurple` - style color purple
- `colorRed` - style color red
- `colorWhite` - style color white
- `colorYellow` - style color yellow
- `colorLigthBlue` - style color light blue
- `colorLightCyan` - style color light cyan
- `colorLightGray` - style color light gray
- `colorLightGreen` - style color light green
- `colorLightPurple` - style color light purple
- `colorLightRed` - style color light red
- `colorLightYellow` - style color light yellow
- `styleBlink` - style blinking
- `styleBold` - style bold
- `styleDim` - style dim
- `styleHidden` - style hidden
- `styleInvert` - style invert
- `styleUnderline` - style underline

### CLI Output Methods

- `dim(string $text, string $end = PHP_EOL): Output` - print dim style text
- `echo(string $text = '', string $end = PHP_EOL): Output` - Print text to _stdout_
- `error(string $text, string $end = PHP_EOL): Output` - print error text
- `grid(array $data, array $options = []): Output` - print grid
  - Options:
    - `indent` - number of spaces to indent
    - `padding` - column padding (default: `4`)
    - `style` - apply style to column, like `['style' => ['name' => 'colorBlue']]`
- `info(string $text, string $end = PHP_EOL): Output` - print info text
- `ok(string $text, string $end = PHP_EOL): Output` - print success text
- `warn(string $text, string $end = PHP_EOL): Output` - print warning text
- `static register(string $name, callable $callback) ` - register style method
- `stderr(string $text, string $end = PHP_EOL): Output` - output to _stderr_
- `stdout(string $text = '', string $end = PHP_EOL): Output` - output to _stdout_
- `styleIndent(int $number): Output` - indent style

## File

`Lark\File` is used to handle files.

```php
use Lark\File;

$file = new File('./my-file.txt');
if($file->write('contents'))
{
    // ...
}

$contents = $file->read();
```

`Lark\Json\File` is used for JSON files.

```php
use Lark\Exception as LarkException;
use Lark\Json\File as JsonFile;

$file = new JsonFile('./my-file.json');
$file->write(['name' => 'test']);

try
{
    $value = $file->read();
}
catch(LarkException $ex)
{
    // exception is throw on JSON decode error
    echo 'Failed to decode JSON file: ' . $ex->getMessage();
}
```

### File Methods

- `delete(): bool` - delete a file
- `exists(): bool` - check if file exists
- `existsOrException()` - if file does not exist throw exception
- `path(): string` - file path getter
- `read()` - read file contents
- `write($data, $append = false, $lock = true): bool` - write file contents

## Timer

`Lark\Timer` works as a timer.

```php
$timer = new Lark\Timer;

usleep(500000);
echo $timer->elapsed(); // 0.5001s

sleep(1);
echo $timer->elapsed(); // 1.5014s

sleep(2);
// get elapsed since last Timer::elapsed()
// or Timer::elapsedSinceLast() was invoked
echo $timer->elapsedSinceLast(); // 2.0003s

echo $timer->elapsed(); // 3.5018s
```

## Helpers

Helpers are global helper functions.

### Helper `app()`

Access the main `App` instance using the `app()` function.

```php
app()->use('[...]');
```

### Helper `db()`

The `db()` function is a database collection instance helper.

```php
// when using default connection ID
// "[database]$[collection]"
$db = db('app$users');
// or "database", "collection"
$db = db('app', 'users');

// when using non-default connection ID
// "[connectionId].[database].[collection]"
$db = db('myDb$app$users')
// or "connectionId", "database", "collection"
$db = db('myDb', 'app', 'users');

// when using a App\Model class with DBS (database string)
$db = db(App\Model\User::class);
```

> Read more in [Database Connections](#database-connections).

### Helper `dbdatetime()`

The `dbdatetime()` function returns a `MongoDB\BSON\UTCDateTime` object.

```php
$dbDt = dbdatetime();
$dt = $dbDt->toDateTime(); // DateTime object

// with milliseconds
$dbDt = dbdatetime(strtotime('-1 day') * 1000);
```

### Helper `debug()`

The `debug()` function is a debugger and logging helper. When called the `debug()` function will append the debugger info and send to logger (`Logger::debug()`).

```php
debug('test');
// same as:
// Debugger::append('test');
// (new Logger)->debug('test');

// title/name can be used:
debug('test', ['info' => 'here');
// same as:
// Debugger::append(['info' => 'here'])->name('test');
// (new Logger)->debug('test', ['info' => 'here']);

// title/name and group/channel can be used:
debug('test', ['info' => 'here'], 'name');
// same as:
// Debugger::append(['info' => 'here'])->name('test')->group('name');
// (new Logger('name'))->debug('test', ['info' => 'here']);
```

> Also see [`x()`](#helper-x) helper function.

> Read more in [Debugger](#debugger) and [Logging](#logging).

### Helper `env()`

The `env()` function is an environment variables helper.

```php
$dbName = env('DB_NAME');
```

> Read more in [Environment Variables & Configuration](#environment-variables--configuration).

### Helper `f()`

The `f()` function returns a formatted string.

```php
echo f('First value: {}, second value: {}', 'one', 'two');
// First value: one, second value: two

// placeholder names can be used
echo f('Name: {name}, age: {age}', 'Test', 25);
// Name: Test, age: 25

// array keys and placeholder names can be used
// regardless of key/value order
echo f('Name: {name}, age: {age}', ['age' => 25, 'name' => 'Test']);
// Name: Test, age: 25
```

### Helper `halt()`

The `halt()` function can be used to immediately return an HTTP response status code and optional JSON message.

```php
halt(404, 'Resource not found');
// returns HTTP response status code 404
// with JSON body {"message": "Resource not found"}

// use custom JSON response
halt(500, ['error' => 'message', 'context' => ['test']]);

// halt without message
halt(500);
```

### Helper `logger()`

Access a `Logger` instance using the `logger()` function.

```php
logger('channel')->info('message', ['context']);
```

> Read more in [Logging](#logging).

### Helper `p()`

The `p()` function outputs formatted (HTML/CLI) variables.

```php
p('test', 'this');
p(['my' => 'array']);
```

### Helper `pa()`

The `pa()` function is a variable printer.

```php
pa('test', 'this', ['and' => 'this'], 'end');
// test this
// Array
// (
//    [and] => this
// )
// end
```

### Helper `req()`

Access the `Lark\Request` instance using the `req()` function.

```php
var_dump(
    req()->path()
);
// string(1) "/"
```

> Read more in [Request](#request).

### Helper `res()`

Access the `Lark\Response` instance using the `res()` function.

```php
res()->contentType('application/json');
```

> Read more in [Response](#response).

### Helper `router()`

Access the `Lark\Router` instance using the `router()` function.

```php
router()->get('/', function() {
    return 'home';
});
```

> Read more in [Routing](#routing).

### Helper `x()`

The `x()` function is a debugger and dumper helper. When called the `x()` function (or `Lark\Debugger::dump()`) will dump all debugger info objects and stop execution.

```php
x('value', ['test' => 'this']);
```

> Also see [`debug()`](#helper-debug) helper function.

> Read more in [Debugger](#debugger).
