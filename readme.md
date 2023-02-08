# Simples ORM para conexão com banco de dados MySQL

## Configuração

Criar constantes que serão usados na conexão com banco MySQL

```dotenv
# Exemplo
ORM_DATABASE_HOST=localhost
ORM_DATABASE_NAME=dbname
ORM_DATABASE_USER=dbuser
ORM_DATABASE_PASSWORD=dbpassword
ORM_DATABASE_CHARSET=utf8
```

## Como usar

Crie uma class para a entidade `extends` a `\FVCode\Orm\Model` e tem as
propiedades `$table` e `$id` como visibilidade `protected`.

```php
<?php

namespace FVCode\Orm\Exemples;

class User extends \FVCode\Orm\Model
{
    protected $table = 'user';
    protected $id = 'id';
}
```

Agora é possivel realizar as operações de CRUD

```php
# Consultar um regsitro pelo ID

// Saída será um array com todas as colunas da tabela user
// out: ['id' => 1, ...]
$user = User::get(1);
 
# Inserção
// Saída será o id do registro inserido
$id = User::cadastrar(['name' => 'Fernando']);

# Atualização
User::atualizar(['name' => 'Valler'], $id);

# Deleção
User::deletar($id);
```