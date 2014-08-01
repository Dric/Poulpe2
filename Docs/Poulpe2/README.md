# Wikitten

Wikitten is a small, fast, PHP wiki, and the perfect place to store your notes, code snippets, ideas, etc.

**This repository is a fork of Wikitten.**

Check out the **[project website](http://wikitten.vizuina.com)** for more details and features.

[![Wikitten](https://raw.githubusercontent.com/jeffersonmartin/Wikitten/master/wikitten_clean.png)](http://wikitten.vizuina.com)

### Features of this Fork (github.com/jeffersonmartin/Wikitten)
* Clean theme for business team use
* Creating / updating files directly through the web interface. 
* Integration of htaccess and htpasswd for basic user authentication

### Requirements

* PHP `5.3+`
* The Apache webserver (with `mod_rewrite`)

### Installation

* [Download](https://github.com/victorstanciu/Wikitten/archive/master.zip) the latest version or clone the [repository on GitHub](https://github.com/victorstanciu/Wikitten)
* After extracting the archive, drop the files somewhere in your DocumentRoot, or make a separate Apache [VirtualHost](http://httpd.apache.org/docs/2.2/mod/core.html#virtualhost) (this is the way I currently use it myself)
* That's it. There's a `library` directory in the installation folder. Everything you place in there will be rendered by the wiki. If there's an `index.md` file (such as the one you are reading now) in that folder, it will be served by default when accessing the wiki.

  You don't have to use the `library` directory if you don't want to, you can configure Wikitten using
  the `config.php` file. Simply copy the `config.php.example` file found in the site root to `config.php`,
  and change the values of the constants defined inside.

### Mod Rewrite and Authentication with htaccess

In `/etc/apache2/` or another non-web accessible location of your choice, create an `.htpasswd` file and use one of the htpasswd generators on the web to create user accounts. If you do not use `/etc/apache2/.htpasswd` as your path, be sure to update it in the `.htaccess` file below.

In the root directory of your wiki (Ex. `/var/www/mywiki/`), create a new .htaccess file and copy the following syntax in:

	AuthName "My Wiki"
	AuthUserFile /etc/apache2/.htpasswd  
	AuthType Basic  
	require valid-user  
	<IfModule mod_rewrite.c>
		RewriteEngine On  
    	RewriteBase /  
    	RewriteCond %{REQUEST_FILENAME} !-f  
    	RewriteCond %{REQUEST_FILENAME} !-d  
    	RewriteRule . /index.php [L]  
	</IfModule>

### Editing a Page/Topic

In `config.php`, you will need to uncomment the `ENABLE_EDITING` line.

In the top right corner of the page, there is a black `Toggle Source` button. When you press this a simple text editor with markdown syntax highlighting will appear. Just start typing using Markdown syntax and click Save Changes at the bottom of the page. 

### Creating a New Folder/Page/Topic

To create a new page or folder structure, in the address bar of your browser, enter the full URL of the page that you want to use for your page. Any folders that you type that don't exist will be created automatically.  

New Page Example `http://mywiki.xyz.com/mynewpage.md`  
New Page and Folder Example `http://mywiki.xyz.com/folder1/folder2/mynewpage.md`  

When the page loads, it will say Page Not Found. You will also see a blue button `Create this Page`. Once you click the button the folders and page will be created.  

Once a page is created, it can only be deleted at the operating system level (via SSH/SFTP/etc).

### JSON Front Matter (meta data)

Wikitten content can also be tagged using a simple but powerful JSON Front Matter system, akin to [Jekyll's YAML Front Matter](https://github.com/mojombo/jekyll/wiki/YAML-Front-Matter). Defining a custom title, tags, or other
relevant data for a specific page is just a matter of adding a special header at the start of your files, like so:

    ---
    "title": "My Custom Page Title",
    "tags": ["my", "custom", "tags"],
    "author": "Bob"
    ---

    # Hello, world!

    This is my cool wiki page.

Wikitten will intelligently grab this data, and use it for things like meta keywords, the
page title, and maybe eventually search indexing. All the information provided in this
header is passed as-is to the views, so future components and plugins may also make use of it.

**Note:** The JSON header is expected to be a JSON hash, but to simplify things, Wikitten lets you leave out the starting an ending `{ }` brackets if you want. Everything else in the JSON syntax still applies:

- Strings (i.e: `title` must be written within double quotes: `"title"`)
- Values must be seperated with a comma character, even if its the only value in a line.

### Roadmap

Some of the features I plan to implement next:

* [Pastebin](http://pastebin.com/) API integration. I think it would be cool to share snippets on Pastebin (or a similar service) with a single click
* Search in files

### Special thanks go to:

* [Victor Stanciu](http://victorstanciu.ro/), the main developer of Wikitten
* [Michel Fortin](http://michelf.ca/home/), for the [PHP Markdown parser](http://michelf.ca/projects/php-markdown/).
* [Marijn Haverbeke](http://marijnhaverbeke.nl/), for [CodeMirror](http://codemirror.net/), a JavaScript code editor.
* [Nicolas Loeuileet](http://github.com/nicosomb), for his [Wikitten fork](http://github.com/nicosomb/Wikitten) with the ability to create pages and folders from the browser.
* [Jefferson Martin](http://github.com/jeffersonmartin/) for the [Wikitten fork](http://github.com/jeffersonmartin/Wikitten) with clean theme and htpasswd integration
* Twitter, for the [Bootstrap](http://twitter.github.com/bootstrap/) CSS framework.
* All Vectors, for the [free cat vector](http://www.allvectors.com/cats-vector/) silhouette I used in making the logo.

