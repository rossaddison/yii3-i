<h6 id="ubuntu">Installation Summary: (...views/invoice/info/ubuntu.php)</h6>
<b>19th June 2022</b>
<p>Aim: To maintain a list of commands for installation purposes along with Virtual Host Html
</p>
<b><code>/etc/apache2/sites-available/invoice.conf</code></b>
<xmp>
<VirtualHost *:80>
   DocumentRoot /var/www/html/yii-invoice/public
   <Directory  /var/www/html/yii-invoice/public/>
        Options +Indexes +Includes +FollowSymLinks +MultiViews
        AllowOverride All
        Require local
   </Directory>
</VirtualHost>
</xmp>

<b><code>/etc/apache2/sites-available/000-default.conf</code></b>
<xmp>                                                                                            
<VirtualHost *:80>
    <Directory /var/www/html/>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require local
    </Directory>
    ServerAdmin webmaster@localhost
    DocumentRoot /var/www/html
    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
</xmp>
<code>In order to clear the assets cache in debug mode. The Administrator must give permission on the public assets folder: sudo chown -R username /var/www/html/yii-invoice/public/assets</code>
