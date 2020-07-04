# What is zr30101
zr30101 is an open-source rapid development web framework for use in building dynamic web sites with content management system written in PHP and paired with a MySQL or MariaDB database

<hr>

## Server Requirements
<ul>
<li>Apache Web server</li>  
<li>PHP version 5.6 or newer is recommended.</li>
<li>MySQL version 5.5 or newer is recommended.</li>
<li>MariaDB version 10 or newer is recommended.</li>
</ul>  

## Installation

### Using shell script
1. Clone or Download https://github.com/z3r0101/tools/blob/master/z3r0101.sh
```
# git clone https://github.com/z3r0101/tools/blob/master/z3r0101.sh
- or -
# wget https://github.com/z3r0101/tools/blob/master/z3r0101.sh
```
2. Copy the z3r0101.sh to your www root directory (E.g. /var/www)
3. Make it executable
```
# chmod +x z3r0101.sh
```
4. Syntax: ./z3r0101.sh [create|remove] [website name]
```
E.g.
# ./z3r0101.sh create website-sample
```

### Manual steps:
<ol>
  <li>Create a website directory</li>
  <li>Create the following directories under your website root directory
      <ul>
        <li>application</li>
        <li>vendors</li>
        <li>compiles (This directory must have a write permission)
            <ul>
              <li>cms (This directory must have a write permission)</li>
            </ul>  
        </li>
        <li>assets
          <ul>
            <li>uploads (This directory must have a write permission)
                <ul>
                  <li>temp (This directory must have a write permission)</li>
                </ul>  
            </li>
          </ul>  
        </li>  
        <li>www</li>
      </ul>
  </li>
  <li>Download the following files under your website root directory
      <ul>
        <li>https://raw.githubusercontent.com/z3r0101/siteroot/master/.htaccess</li>  
        <li>https://raw.githubusercontent.com/z3r0101/siteroot/master/.gitignore</li>
      </ul>  
  </li>
  <li>Under your application directory.
    Run the clone command:<br>git clone https://github.com/z3r0101/application.git .
  </li>
  <li>Under your vendors directory.
    Run the clone command:<br>git clone https://github.com/z3r0101/vendors.git .
  </li>
  <li>The www directory will be your working web files directory<br>
    Available project sample:
    <ul>
      <li><a href="https://github.com/z3r0101/www-basic-cms">https://github.com/z3r0101/www-basic-cms</a></li>
    </ul>  
  </li>
</ol>

## z3r0101 framework structure
<table>
  <tr>
    <td width="50%"><ul><li>application</li></ul></td>
    <td width="50%" valign="top">This folder contains the framework core files. It is not advised to make changes in this directory or put your own application code into this directory.</td>
  </tr>  
  <tr>
    <td><ul><li>vendors</li></ul></td>
    <td valign="top">Third-party libraries or functions</td>
  </tr>  
  <tr>
    <td>
      <ul>
        <li>
          compiles
          <ul><li>cms</li></ul>
        </li>
      </ul>  
    </td>
    <td valign="top">Generated by Blade Templates</td>
  </tr>
  <tr>
    <td>
      <ul>
      <li>
        uploads
        <ul><li>temp</li></ul>
      </li>
      </ul>
    </td>
    <td valign="top">CMS file upload directory storage</td>
  </tr>  
  <tr>
    <td><ul><li>www</li></ul></td>
    <td>Your dev working project files directory<br>Sample project: https://github.com/z3r0101/www-basic-cms</td>
  </tr>  
</table>  
