
Example of lightweight payment microservice.

Supported providers: *Paypal*

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

**USAGE**
