
### Carrinho de e-commerce :computer:

<img src="https://img.shields.io/static/v1?label=Licese&message=MIT&color=blue&style=for-the-badge"/> <img src="https://img.shields.io/static/v1?label=PHP&message=7.3.30&color=purple&style=for-the-badge&logo=PHP"/> <img src="https://img.shields.io/static/v1?label=LARAVEL&message=8.54&color=red&style=for-the-badge&logo=LARAVEL"/>

### Tópicos

:small_blue_diamond: [Descrição do projeto](#descrição-do-projeto)

:small_blue_diamond: [Features](#features)

:small_blue_diamond: [Pré-requisitos](#pré-requisitos)

:small_blue_diamond: [Como rodar a aplicação ](#como-rodar-a-aplicação-arrow_forward)

:small_blue_diamond: [Documentação dos endpoints ](#documentação-dos-endpoints)

:small_blue_diamond: [Como rodar os testes ](#como-rodar-os-testes)


## Descrição do Projeto

Desenvolver um sistema de carrinho de e-commerce.

### Features
- Adicionar produto no carrinho
- Remover um item do carrinho
- Atualizar a quantidade de um item no carrinho
- Limpar o carrinho
- Recuperar o carrinho
- Retornar um JSON com o carrinho completo

> Status do Projeto: Concluido :heavy_check_mark:

## Pré-requisitos

:warning: [Docker](https://www.docker.com/) :whale:

:warning: [Docker compose](https://docs.docker.com/compose/) :octopus:

## Como rodar a aplicação :arrow_forward:

No terminal, clone o projetom entre na pasta e execute o seguinte comando:

```
$ make install
```

Para cirar o host do projeto basta rodar o seguinte comando:

```
$ sudo make create-hosts
```

Rodar as migrations

```
$ make run-migrations
```

O projeto estará disponível no seguinte host:

```
http://shoppingCart.local
```

## Documentação dos endpoints

Segue o [link](https://documenter.getpostman.com/view/13471330/UUxui9pi) para documentação dos endpoints.

## Como rodar os testes

Para executar os testes é necessário criar uma nova _database_.

Em um browser acesse `localhost:808`, usaremos as seguintes credenciais:

|Server | username  | senha  |
|------------ | ------------ | ------------ |
| mysql | root | root  |

Em `Create database` basta inseri o seguinte nome no campo `shoppingCart_test` :arrow_right: `Save`

Em seguida basta rodar o seguinte comando:
```
$ make tests
```

## Linguagens, dependencias e libs utilizadas :books:

- [PHP](https://www.php.net/)
- [Laravel](https://laravel.com/docs/8.x)
- [Repository](https://packagist.org/packages/prettus/l5-repository)
## Licença

The [MIT License]() (MIT)

Copyright :copyright: 2021 - Carrinho de e-commerce



