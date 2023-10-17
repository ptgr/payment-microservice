
Example of lightweight payment microservice.

**FEATURES:**
- Payment gateway integration (currently only Paypal is supported)
- Generates payment link for later usage
- Accepts providers notification
- Stores token and payment history
- Supports multiple account of same provider for example multiple paypal accounts for different companies

**API DOCS**
SWAGGER UI - https://ptgr.localhost/api/doc

**REQUIREMENTS:**
  *Docker*
  
**INSTALLATION**
1) Copy **.env.example** to **.env**.
2) Open the **.env** and change these values:
	 *PAYPAL_ACCOUNT_SANDBOX,*
	 *PAYPAL_ACCOUNT_SANDBOX_SECRET,*
	 *POSTGRES_PASSWORD,*
	 *[REPLACE_PASSWORD]*
3) Open terminal and run `docker-compose up`.
4) Open PHP container and run initial data loader: `php bin/console doctrine:fixtures:load`.

**ACCESS DATABASE**
http://localhost:8086/

System: *PostgreSQL*
Server: *database*
User: *payment_user*
Password: *[POSTGRES_PASSWORD]*
Database: *db_payments*