Example of a lightweight payment microservice written in PHP (Symfony), Caddy and PostgreSQL.

**FEATURES**
- Payment gateway integration (currently only Paypal is supported)
- Generates payment link for later usage
- Accepts providers notification
- Stores token and payment history
- Supports multiple account of same provider for example multiple paypal accounts for different companies

**REQUIREMENTS**  
*Docker*
  
**INSTALLATION**
1) Clone repository using `git clone https://github.com/ptgr/payment-microservice.git`
2) Create copy of **.env.example** and rename it to **.env**
3) Open the **.env** file and change these values according to your credentials  
	 *PAYPAL_ACCOUNT_SANDBOX,*  
	 *PAYPAL_ACCOUNT_SANDBOX_SECRET,*  
	 *POSTGRES_PASSWORD,*  
	 *[REPLACE_PASSWORD]*  
4) Open terminal and run `docker-compose up`
5) Open PHP container and run initial data loader: `php bin/console doctrine:fixtures:load`
   
**API DOCS**   
SWAGGER UI - https://ptgr.localhost/api/doc

**DATABASE ACCESS**   
http://localhost:8086/

System: *PostgreSQL*  
Server: *database*  
User: *payment_user*  
Password: *[POSTGRES_PASSWORD]*  
Database: *db_payments*
