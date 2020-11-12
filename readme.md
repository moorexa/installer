# Moorexa Installer

> Before using this, ensure you have PHP installed on your machine and you can access it from the Terminal/Command Line Interface.

The installer makes it easy to download and install any stable release of moorexa programatically, and then registers moorexa to your system path so you can tap into all of the amazing features built into the ASSIST manager.

The installer also gives you the convinence for creating new projects without downloading a fresh copy of moorexa, and much more.


## How to install programmatically
1. Open your terminal or search for ("cmd" window users)
2. Check your PHP version with 
```bash
php ---version
```
3. You should at least have php 7.2 installed.
4. Copy the code below to start installation;

```bash
php -r "copy('http://moorexa.com/raw-installer', 'installer.php');"; php installer.php;
```
5. Paste command copied into your terminal and hit the ENTER key to start operation.
6. Wait for the process to finish
7. Enjoy building with PHP on Moorexa..


## How to install manually
1. Goto this address from your browser ```http://moorexa.com/get-installer``` 
2. Save the ```installer.php``` file 
3. Open your terminal or command line
4. Navigate to where the ```installer.php``` file is and run the following code below
```bash
php installer.php;
```