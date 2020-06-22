# What is zr30101
zr30101 is an open-source rapid development web framework for use in building dynamic web sites with content management system written in PHP and paired with a MySQL or MariaDB database

<hr>

## Server Requirements
PHP version 5.6 or newer is recommended.
MySQL version 5 or newer is recommended.
MariaDB version 10 or newer is recommended.

## Installation

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
        <li>uploads (This directory must have a write permission)
            <ul>
              <li>temp (This directory must have a write permission)</li>
            </ul>  
        </li>
        <li>www</li>
      </ul>
  </li>
  <li>Download the following files under your website root directory
      <ul>
        <li>https://raw.githubusercontent.com/z3r0101/webroot/master/index.php</li>  
        <li>https://raw.githubusercontent.com/z3r0101/webroot/master/.htaccess</li>  
        <li>https://raw.githubusercontent.com/z3r0101/webroot/master/.gitignore</li>
      </ul>  
  </li>
  <li>Under your application directory.
    Run the clone command:<br>git clone https://github.com/z3r0101/application.git .
  </li>
  <li>Under your vendors directory.
    Run the clone command:<br>git clone https://github.com/z3r0101/vendors.git .
  </li>
  <li>The www directory will be your working web files directory<br>
    You can download web project samples in http://z3r0101.com
  </li>
</ol>

## z3r0101 framework structure
<table>
  <tr>
    <td width="50%"><ul><li>application</li></ul></td>
    <td width="50%" valign="top">z3r0101 framework core files</td>
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
    <td>Your dev working project files directory</td>
  </tr>  
</table>  
