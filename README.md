# Open Platypus


## Requirements

Welcome to OpenPlatypus.  It has a number of requirement to function as included below:

* PHP >= v5.5.9
    * OpenSSL PHP Extension
    * PDO PHP Extension
    * Mbstring PHP Extension
    * Tokenizer PHP Extension
* MySQL >= v5.6.6

	Note:
	Platypus frequently issues SQL statements of the form:

	`SELECT * FROM t1 WHERE t1.a IN (SELECT t2.b FROM t2 WHERE ...)` 
	
	These queries are not optimised well by the 5.5 series of Mysql resulting 
	in full table scans (sometimes even nested full table scans).

	Mysql from version 5.6.6 comes with the "Semi-Join" and "Materialization" 
	optimisation methods that mitigate the issue and evaluate the queries in 
	more efficient ways. Thus, it is highly recommended to use Mysql 5.6.6 or 
	newer and ensure that the "Materialization" optimisation method is 
	activated. 
	[See](http://dev.mysql.com/doc/refman/5.6/en/subquery-optimization.html).



## Installation

Platypus needs a php installation as well as a mysql database.


### Database Setup
1. Create an empty database schema called `platypus2`. 
	* If you choose another name: change the settings in 
	`app/config/[environmentName]/database.php`.

2. Create a database user called `platypus2` with full permissions.


### Files
1. `git clone` to a location of your choice (`/path/to/platypus`)

2.  Make sure the `app/storage` folder and all its files and sub-folders 
	are writable by the web-server

3.  Publish the `public` folder on your webserver. 
        * E.g. by symlinking 
        	`cd /path/to/www; ln -s /path/to/platypus/public platypus`
        * Note: Platypus' default configuration assumes access via 
        		`http://your.server/platypus`. 
        	* If you would prefer something else, edit `public/.htaccess` and 
        		replace all occurences of platypus with the appropriate path.


### Environment Configuration
1. Edit `bootstrap/start.php`. 
    * Add an environment (for your custom settings) to be used when Platypus 
    	is run on your machine 
        * Add an entry to the `detectEnvironment` array as follows: 
    	* `'environmentName' => array('hostNameOne')`, where:
            * `environmentName` could be anything you like
            * `hostNameOne` is the output of the `hostname` command at your 
            	terminal. You would add more than one e.g. if you use 
            	multiple machines

2. Create a folder `app/config/environmentName`
3. Copy the contents of `app/config/local` into the created folder.

4. Create a file `.env.environmentName.php` in the platypus root 
	(`/path/to/platypus`) directory as shown below and 
	put the correct database password inside.

```php
<?php
	return array(
		'Platypus2_mysql_password' => 'SuperSecretDatabasePassword',
	);
?>
```

Everything should be set up now and it is time to fire up platypus. 
* Visit *http://your.server.name/platypus/setup*. 
If everything is ok, it should ask you to initialise the primary admin user. 
After submitting, Platypus will initialise the the database tables and 
everything is good to go.


### Webserver Setup
Platypus loads quite a few Javascript and CSS resources. Thus, it is highly 
recommended to ensure the cache settings of the webserver allow the client 
to cache static resources without sending requests to the server with each 
page load.


## Credits

The Laravel framework is open-sourced software licensed under the 
[MIT license](http://opensource.org/licenses/MIT).


## License

Platypus
Copyright (C) 2015 The Robotics Design Lab at The University of Queensland

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
