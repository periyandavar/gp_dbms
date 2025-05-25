# GP DBMS Library

The GP DBMS Library is a database management system written in PHP. It provides tools for interacting with databases, managing models, querying data, and handling relationships. The library is designed to make database interaction easier and more efficient.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Getting Started](#getting-started)
- [Features](#features)
- [Classes](#classes)
  - [DBQuery](#dbquery)
  - [Database](#database)
  - [Model](#model)
  - [Events](#events)
- [Usage](#usage)
  - [Frame Queries](#framequeries)
    - [Select Query](#select-query)
    - [Insert Query](#insert-query)
    - [Update Query](#update-query)
    - [Delete Query](#delete-query)
  - [Running Queries](#running-queries)
  - [Transaction Management](#transaction-management)
  - [Creating custom DB Driver](#creating-custom-db-driver)
  - [Creating an ORM Model](#creating-an-orm-model)
  - [Performing CRUD Operations with ORM Model](#performing-crud-operations-with-orm-model)
  - [Handling Relationships](#handling-relationships)
  - [Creating Event](#creating-event)
  - [Creating and Loading ORM Models with Relations](#creating-and-loading-orm-models-with-relations)
- [Example](#example)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)
- [Author](#author)

---


## Requirements

- PHP 7.4 or higher.
- Composer (optional but recommended for autoloading).

---


## Installation

You can install `gp_dbms` using Composer. Run the following command in your terminal:

```
composer require gp/dbms
```
---

## Getting Started

After installation, you can start using the package by including the autoloader:

```
require 'vendor/autoload.php';
```
---

# Features

- **Flexible Database Connections**: Supports multiple database drivers like `PDO` and `mysqli`.
- **Query Builder**: Easily create and execute SQL queries with chaining methods.
    - **Query Building**
        - **Select**: Supports selecting specific columns or all columns.
        - **Insert**: Allows inserting data with direct values and function-based values.
        - **Update**: Updates data with field-value pairs and optional conditions.
        - **Delete**: Deletes rows from a table with optional conditions.

    - **Where Conditions**
        - `AND` and `OR` conditions can be applied using the `where()` and `orWhere()` methods.
        - Supports single conditions, array-based conditions, and parameterized conditions.
    - **Joins**
        - Supports `INNER JOIN`.
    - **Ordering and Limiting**
        - Sorting via `orderBy()` method.
        - Limiting rows with `limit()` method.
    - **Query Resetting**
        - Resets the query state using `reset()` or `_resetQuery()`.
    - **Query Execution Helpers**
        - Builds the query string using `getQuery()`.
        - Provides bind values for prepared statements with `getBindValues()`.
    - **Extensibility**
        - The `DBQuery` class is designed to be extended by database-specific drivers. For instance:
- **Object-Relational Mapping (ORM)**: Map tables to PHP classes for seamless data manipulation.
- **Relationships**: Handle `HasOne` and `HasMany` relationships with lazy and eager loading.
- **Eager & Lazy Loading**: Supports Eager and lazy loading.
- **Lifecycle Events**: Trigger hooks like `onSave`, `onDelete`, and `onUpdate` for models.
- **Exception Handling**: Provides meaningful error messages through `DatabaseException`.
- **Transaction Support**: Execute operations within transactions with commit/rollback capabilities.
- **Extensibility**: Extend models and relationships to customize functionality as needed.

## Clasess

### DBQuery

The `DBQuery` class is a versatile and extensible query builder designed for database operations. It provides methods to build `SELECT`, `INSERT`, `UPDATE`, and `DELETE` queries with various features like joins, where conditions, ordering, grouping, and more. This class is meant to be extended by specific database drivers.

#### Methods Documentation

Core Methods
| Method | Description |
|------------|-----------------|
| select(...$columns): DBQuery | Builds the SELECT query with the specified columns. |
| insert(string $table, array $fields, array $funcfields = []): DBQuery | Constructs the INSERT query. |
| update(string $table, array $fields, mixed $where = null, ?string $join = null): DBQuery | Builds the UPDATE query. |
| delete(string $table, mixed $where = null): DBQuery | Creates the DELETE query. |
| where(array\|string ...$args): DBQuery | Adds AND conditions to the query. |
| orWhere(array\|string ...$args): DBQuery | Adds OR conditions to the query. |

---

Helper Methods
| Method | Description |
|------------|-----------------|
| getQuery(): string | Returns the constructed query string. |
| getBindValues(): array | Returns the bind values for prepared statements. |
| reset(): void | Resets the query state. |

---
#### Notes

- All queries are parameterized to prevent SQL injection.
- Ensure proper handling of bind values in the database execution layer.
- Extend this class to add database-specific functionality as needed.

---
### Database

The `Database` class is an abstract superclass designed to serve as the base for all database-related operations. It provides foundational methods for query building, query execution, and transaction management across various database drivers. All driver-specific classes should extend this class and implement the abstract methods to provide database-specific functionality.

#### Methods Documentation

Core Methods
| Method | Description |
|------------|-----------------|
| query(string $query, array $bindValues = []): bool | Executes a raw SQL query with optional bind values. |
| execute(): bool | Executes the previously built query using DBQuery. |
| set(string $name, string $value): bool | Sets a SQL variable. |
| begin(): bool | Starts a transaction. |
| commit(): bool | Commits the current transaction. |
| rollback(): bool | Rolls back the current transaction. |
| getOne() | Fetches a single row from the result set. |
| getAll() | Fetches all rows from the result set. |
| setQuery($query) | Sets a custom query. |
| setDbQuery($dbQuery) | Sets the DBQuery object. |


Abstract Methods (To Be Implemented by Drivers)

| Method | Description |
|------------|-----------------|
| close() | Closes the database connection. |
| runQuery(string $sql, array $bindValues = []): bool | Executes a query and returns a success flag. |
| executeQuery(): bool | Executes the previously built query and stores the result. |
| fetch() | Fetches a single row from the result set. |
| getInstance(string $host, string $user, string $pass, string $db, array $configs = []): Database | Retrieves a singleton instance of the database driver. |
| insertId(): int | Returns the ID of the last inserted row. |
| escape(string $value): string | Escapes a string for safe usage in queries. |

Properties

| Property | Description |
|--------------|-----------------|
| $con | Holds the database connection object. |
| $result | Stores the result set of a query. |
| $dbQuery | Instance of the DBQuery class for query building. |
| $query | Contains the executed query string. |
| $bindValues | Holds the bind values for the query. |
| $instance | Singleton instance of the database class. |

---

#### Notes
- Error Handling: The DatabaseException is thrown for invalid method calls or query errors.
- Extensibility: Extend this class to implement database driver-specific functionality.
- Security: All queries should use parameterized statements to prevent SQL injection.
---

### Model

The `Model` class is an abstract base class for all database models. It provides methods for common ORM operations such as creating, reading, updating, and deleting records. It also includes support for handling relationships through HasMany and HasOne relations.
- **HasMany** - The HasMany class represents a one-to-many relationship between two models. For example, a User model can have many Post models.
- **HasOne** - The HasOne class represents a one-to-one relationship between two models. For example, a User model might have one Profile model.


#### Methods Documentation

| Method                | Description                                                                 |
|---------------------------|---------------------------------------------------------------------------------|
| save($_is_dirty_update) | Saves the model. Inserts a new record or updates an existing one.               |
| delete()                | Deletes the model based on its unique key.                                      |
| find($_identifier)      | Finds a record by its unique identifier.                                        |
| findAll($_query)        | Finds all records matching the query.                                           |
| insert($_data)          | Inserts a new record into the database.                                         |
| update($_data, $_where) | Updates an existing record in the database.                                     |
| toDbRow()               | Converts the model to a database row format.                                    |
| fromDbRow($_data)       | Loads the model from a database row.                                            |
| setDbQuery($query)      | Sets the DBQuery instance for the model.                                      |
| triggerEvent($_event)   | Triggers an event such as beforeSave, afterSave, etc.                       |
| getUniqueId()           | Returns the unique key and its value for the model.                             |
| getTableName()          | Returns the name of the database table associated with the model.               |

#### Notes

- Extensibility: Override getTableName and getUniqueKey to customize table names and primary keys.
- Events: Use event hooks to add custom logic during save, update, insert, or delete operations.
- Lazy vs Eager Loading: Use lazy loading for on-demand related data and eager loading for preloading related models.

### Events

The `Events` class in the ORM system is an abstract class that defines constants for various lifecycle events and provides a mechanism for handling these events. It is primarily used to trigger specific actions during the lifecycle of a model, such as before or after saving, deleting, loading, inserting, or updating.

#### Methods Documentation

| Constant          | Description                                      |
|-----------------------|-----------------------------------------------------|
| EVENT_BEFORE_SAVE   | Triggered before a model is saved.                  |
| EVENT_AFTER_SAVE    | Triggered after a model is saved.                   |
| EVENT_BEFORE_DELETE | Triggered before a model is deleted.                |
| EVENT_AFTER_DELETE  | Triggered after a model is deleted.                 |
| EVENT_BEFORE_INSERT | Triggered before a model is inserted into the database. |
| EVENT_AFTER_INSERT  | Triggered after a model is inserted into the database. |
| EVENT_BEFORE_UPDATE | Triggered before a model is updated.                |
| EVENT_AFTER_UPDATE  | Triggered after a model is updated.                 |
| EVENT_BEFORE_LOAD   | Triggered before a model is loaded from the database. |
| EVENT_AFTER_LOAD    | Triggered after a model is loaded from the database. |

Abstract Method
| Method                     | Description                                                             |
|--------------------------------|-----------------------------------------------------------------------------|
| handle(Model $_model)        | Handles events for a given model. Must be implemented in derived classes.    |

---

---

---

## Usage
### FrameQueries

#### Select Query
```
$queryBuilder = new DBQuery();

$query = $queryBuilder
    ->select('id', 'name', 'age')
    ->from('users')
    ->where('age', '>', 18)
    ->orderBy('name', 'ASC')
    ->limit(10)
    ->getQuery();

$bindValues = $queryBuilder->getBindValues();
```

#### Insert Query
```
$queryBuilder = new DBQuery();

$query = $queryBuilder
    ->insert('users', ['name' => 'John', 'age' => 25])
    ->getQuery();

$bindValues = $queryBuilder->getBindValues();
```

#### Update Query

```
$queryBuilder = new DBQuery();

$query = $queryBuilder
    ->update('users', ['name' => 'Jane'], 'id = 1')
    ->getQuery();

$bindValues = $queryBuilder->getBindValues();
```

#### Delete Query

```
$queryBuilder = new DBQuery();

$query = $queryBuilder
    ->delete('users', 'id = 1')
    ->getQuery();

$bindValues = $queryBuilder->getBindValues();
```

----

#### Running Queries

```
$database = MySQLDatabase::getInstance('localhost', 'root', 'password', 'test_db');

// Insert Query
$database->insert('users', ['name' => 'John', 'age' => 25])->execute();

// Select Query
$user = $database->select(['id', 'name'])->from('users')->where(['id' => 1])->getOne();

// Update Query
$database->update('users', ['name' => 'Jane'], 'id = 1')->execute();

// Delete Query
$database->delete('users', 'id = 1')->execute();

```
---

### Transaction Management

```
$database->begin();

try {
    $database->insert('users', ['name' => 'Transaction Test', 'age' => 30])->execute();
    $database->update('users', ['age' => 31], 'name = "Transaction Test"')->execute();
    $database->commit();
} catch (Exception $e) {
    $database->rollback();
    // Handle exception
}

```

### Creating custom DB Driver

```
namespace Database\Drivers;

use Database\Database;

class MySQLDatabase extends Database
{
    public function close()
    {
        if ($this->con) {
            $this->con = null; // Close the connection
        }
    }

    public function runQuery(string $sql, array $bindValues = []): bool
    {
        // Implementation for executing a query in MySQL
    }

    protected function executeQuery(): bool
    {
        // Implementation for executing a built query
    }

    public function fetch()
    {
        // Implementation for fetching a single row
    }

    public static function getInstance(string $host, string $user, string $pass, string $db, array $configs = [])
    {
        if (!self::$instance) {
            self::$instance = new self();
            // Initialize the connection here
        }

        return self::$instance;
    }

    public function insertId(): int
    {
        // Implementation for retrieving the last inserted ID
    }

    public function escape(string $value): string
    {
        // Implementation for escaping input values
    }
}
```

#### Creating an ORM Model

```
namespace App\Models;

use Database\Orm\Model;

class User extends Model
{
    public static function getTableName()
    {
        return 'users'; // Specify the database table name (default:classname)
    }

    public static function getUniqueKey()
    {
        return 'email'; // returns primary key name (default: id)
    }

    public function fromDbRow(array $_data)
    {
        // create the model class from db row and return it.
        // by default the values will be loaded by considering the field name 
        // as attribute name in the class
    }

    public function toDbRow()
    {
        // frame the array with respect to db row. by default the 
        // class properties will be considered as the field in the table.
    }

    public function skipInsertOn()
    {
        return [
            'created_at' // the keys needs to be skipped on insert
        ]
    }

    public function getRules()
    {
        return [
            ['email', ['required]] // add validations
        ];
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id'); // Define a HasMany relationship
    }

    public function profile()
    {
        return $this->hasOne(Profile::class, 'user_id', 'id'); // Define a HasOne relationship
    }
}
```

#### Performing CRUD Operations with ORM Model 

```
use App\Models\User;

// Create a new user
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Find a user by ID
$user = User::find(1);

// Update the user's name
$user->name = 'Jane Doe';
$user->save();

// Delete the user
$user->delete();
```

#### Handling Relationships
##### HasMany
```

use App\Models\User;

// Get a user and their posts
$user = User::find(1);
$posts = $user->posts(); // Lazy load

// Eager load posts
$userWithPosts = User::select()->with(['posts'])->one();

```

##### HasOne

```
use App\Models\User;

// Get a user's profile
$user = User::find(1);
$profile = $user->profile(); // Lazy load

// Eager load profile
$userWithProfile = User::select()->with(['profile'])->one();
```

#### Creating Event

```
namespace App\Events;

use Database\Orm\Events;
use Database\Orm\Model;

class AuditEventHandler extends Events
{
    public function handle(Model $_model)
    {
        if ($_model->hasTriggeredEvent(self::EVENT_BEFORE_DELETE)) {
            echo "Audit log: A model is about to be deleted.";
        }

        if ($_model->hasTriggeredEvent(self::EVENT_AFTER_DELETE)) {
            echo "Audit log: A model has been deleted.";
        }
    }
}
```

### Creating and Loading ORM Models with Relations

```

namespace App\Models;

use Database\Orm\Model;

class User extends Model
{
    public static function getTableName()
    {
        return 'users'; // Specify the database table name
    }

    public function posts()
    {
        return $this->hasMany(Post::class, 'user_id', 'id'); // Define a HasMany relationship
    }
}

class Post extends Model
{
    public static function getTableName()
    {
        return 'posts'; // Specify the database table name
    }

    public function user()
    {
        return $this->hasOne(User::class, 'user_id', 'id'); // Define a HasMany relationship
    }
}


// Create a user
$user = new User();
$user->name = 'John Doe';
$user->email = 'john@example.com';
$user->save();

// Add posts for the user
$post1 = new Post();
$post1->title = 'First Post';
$post1->user_id = $user->id;
$post1->save();

$post2 = new Post();
$post2->title = 'Second Post';
$post2->user_id = $user->id;
$post2->save();

// Fetch user with posts
$userWithPosts = User::select()->with(['posts'])->one();

```

### Example

```
require_once 'DatabaseFactory.php';
require_once 'Model.php';

class User extends Model
{
    public function __construct() {
        $this->setTriggerEvent(true);
        $this->setEvents([
            Events::BEFORE_SAVE => BeforeSave::class // and Events
        ]);
    }
    public static function getTableName()
    {
      return 'users';
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    public static function getTableName()
    {
      return 'posts';
    }
}

// Fetch a user and their posts
$user = User::find(1); $posts = $user->posts;
```



## Contributing

Contributions are welcome! If you would like to contribute to gp_validator, please follow these steps:

- Fork the repository.
- Create a new branch (git checkout -b feature/- YourFeature).
- Make your changes and commit them (git commit -m 'Add some feature').
- Push to the branch (git push origin feature/YourFeature).
- Open a pull request.
- Please ensure that your code adheres to the coding standards and includes appropriate tests.

---

## License

This package is licensed under the MIT License. See the [LICENSE](https://github.com/periyandavar/gp_dbms/blob/main/LICENSE) file for more information.

---

## Contact
For questions or issues, please reach out to the development team or open a ticket.

---


## Author

- Periyandavar [Github](https://github.com/periyandavar) (<vickyperiyandavar@gmail.com>)

---