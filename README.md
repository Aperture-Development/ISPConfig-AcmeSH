# ISPConfig-AcmeSH
This project is just a simple script to simplify installing new Acme.SH certificates into ISPconfig using their SoapServer.

## Installation and setup

__**Requirements:**__
- [php7+](https://www.php.net/) with soap and openssl module enabled
- [git](https://git-scm.com/) (preffered), [curl](https://curl.se/) or [wget](https://www.gnu.org/software/wget/) ( to download the project )
- [acme.sh](https://github.com/acmesh-official/acme.sh)
- [ISPConfig](https://www.ispconfig.org/)
- Any code editor you are comfortable with

__**Installation**__
1. Git clone or download the project onto your server ( ``git clone https://github.com/Aperture-Development/ISPConfig-AcmeSH.git <output path>`` )
2. Open the ``acmesh_ispconfig.php`` file and edit the top part with your environment data ( look below to see how to setup ISPConfig )
3. Save and close the file

__**ISPConfig setup**__
1. Log into ISPConfig
2. Select "System" > "Remote Users" and "Add new user"
3. Create a new remote user with the permissions "Server functions", "Sites Domain functions", "Sites Aliasdomain functions", "Sites Subdomain functions" and "Client functions"

If you do not want to specify your ISPConfig username or password inside the file, you can use the ``--username`` and ``--password`` parameters to use temporary environment variables.

## Usage

__**Basic Usage:**__

``acmesh_ispconfig.php -d <domain> [-d <domain>...]``

__**Advanced Parameters:**__
```
Usage: acmesh_ispconfig.php [OPTIONS]

Options:
 -d, --domain DOMAIN     pass DOMAIN to be updated ( required )
 -h, --help              display this help message and exit ( optional )
 -s, --service           provide services to be reloaded after the certificate has been updated ( optional )
 -u, --username          the ISPConfig API username ( optional )
 -p, --password          the ISPConfig API password ( optional )
 -l, --uri               the ISPConfig remote URI ( optional )
 -r, --reloadcmd         the reload cmd to be used to restart services ( optional )
```

__**Automatically run the script after renewing a certificate:**__
When you issue the certificate you need to provide the ``--renew-hook`` parameter to run the php script after your certificate has been sucessfully renewed.  
Example parameter: ``acme.sh --issue -d example.com --renew-hook "/path/to/script/acmesh_ispconfig.php -d example.com -s dovecot -s postfix"``

More informations can be found here: https://github.com/acmesh-official/acme.sh/wiki/Using-pre-hook-post-hook-renew-hook-reloadcmd

## Licence and Info
This project has been developed by [Aperture Development](https://www.Aperture-Development.de) and is licenced under by-sa 4.0.  
You can find more informations about the licence terms inside the [LICENSE](LICENSE) file