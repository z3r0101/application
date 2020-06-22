# application

The main directory in z3r0101 framework. This folder contains the framework core files. It is not advised to make changes in this directory or put your own application code into this directory.

# Setup z30101 framework project

# Manual steps:
<ol>
  <li>Create a website directory</li>
  <li>Create the following directories under your website root directory
      <ul>
        <li>application</li>
        <li>vendors</li>
        <li>compiles (This directory must have write permission)
            <ul>
              <li>cms (This directory must have write permission)</li>
            </ul>  
        </li>
        <li>uploads (This directory must have write permission)
            <ul>
              <li>temp (This directory must have write permission)</li>
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

# z3r0101 framework structure
<ul>
  <li>application</li>
  <li>vendors</li>
  <li>compiles</li>
  <li>uploads</li>
  <li>.htaccess</li>
  <li>index.php</li>
</ul>
