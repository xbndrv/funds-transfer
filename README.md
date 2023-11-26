# Funds transfer Symfony application

Test task.

## Installation

Requires docker and git.

```shell
# loading the project files
mkdir funds-transfer
cd funds-transfer
git init
git remote add origin https://github.com/xbndrv/funds-transfer
git pull origin main

# building production docker image funds_transfer
# based on php-fpm
sudo ./build-prod.sh

# building funds_transfer_db container based on postgres,
# funds_transfer container based on funds_transfer image
# with volumed app directory,
# and funds_transfer_nginx container listening to 8088
sudo docker compose up -d

# Installing dependencies
sudo docker exec funds_transfer composer install

# Running migrations and fixtures
sudo docker exec funds_transfer bin/console doctrine:migrations:migrate
sudo docker exec funds_transfer bin/console doctrine:fixture:load
```

Then open http://localhost:8088/ with a browser or Postman.

## API Endpoints:

`GET /` - list of endpoints.

`GET /clients` - list of clients.  
limit and offset parameters are supported.

`GET /client-accounts?client=1` - list of accounts.

`GET /account-transactions?account=1` - list of transactions, both incoming
and outgoing. Also limit and offset parameters are supported. 
Amounts are provided in cents (real value x100, integer).
Outgoing transactions have negative amount.

`POST /transfer` - transfer money between accounts. Parameters:

> from - Source account ID  
> to - Target account ID  
> amount - Transaction amount in cents (x100)  
> currency - 3-letters currency code, f.e USD or EUR. Must match the 
target account currency.

Transferring between accounts with different currencies is possible only
if exchangerate.host provides corresponding exchange rate or both exchange
rates from source currency to USD and from USD to target currency.

Transferring between accounts with the same currency is possible anyway.

## Testing

Tests require separate database db_test:

```shell
sudo docker exec -it funds_transfer_db bash
psql -U user -W db 
Password: password

CREATE DATABASE db_test;
GRANT ALL PRIVILEGES ON DATABASE db_test TO "user";
quit;
exit 

sudo docker exec funds_transfer bin/console --env=test doctrine:migrations:migrate
sudo docker exec funds_transfer bin/console --env=test doctrine:fixtures:load
```

Running tests:

```shell
sudo docker exec funds_transfer composer test
```

Accounts List (App\Tests\AccountsList)  
✔ No client provided error  
✔ Client not exist error  
✔ Correct client accounts list  

Perform Transaction (App\Tests\PerformTrans  action)  
✔ Parameter lack error  
✔ Account not found error  
✔ Not enough funds  
✔ Currency doesnt match target account  
✔ Conversion to unknown currency  
✔ Same currency transfer  
✔ Eur to gbp conversion  

Transactions List (App\Tests\TransactionsList)  
✔ No account provided error  
✔ Account not exist error  
✔ Account with no transactions  
✔ Incoming and outgoing transactions  
✔ Transactions pagination  


