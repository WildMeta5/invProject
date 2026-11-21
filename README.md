<div align="center"> <h1>NEXSTAR Harlingen Inventory Project</h1> </div>

<h2>Before we start...</h2>
<p>I want to give a huge thanks to the people working at Nexstar Harlingen. They are incredible in what they do and are always willing to help
others. This is my very first time in a work environment and it has been surreal with how everyone manage their time even with other things going around them
, dipping my feet into this was definitely scary but it has helped me improve in more ways then one as after working their I have became more
confident in my work, more communitive with my peers, and not to be afraid of asking questions and that's just the tip of the iceburg!</p>

<p>I want to personally thank:</p>
<li>"Lee" Martinez</li>
<li>Maria</li>
<li>Chris</li>
<li>Adrian</li>
<li>George</li>
<li>Isaias</li>
<p>For all the help and teachings they have given me for the past 3 month. Lee is the one who took a chance with me and wanted me to learn the
real world and how to operate, a long with teaching me how to think outside the box to achieve your goals. Maria watched over me helping me keep up
to speed and help showcase that once work settles down its okay to relax and once problem arise to switch gears and sort it out effectiontly.
Chris has been informative and always teaching me new thing each day, especially with diffrent types of wires like how to make keystones, double check
them and to be organize the first time so that it is easy to fix later down the line with anything. Adrian is the brains (and muscles) that
maintain and ensure that all systems are working the way they should be and teach me in a easy to understanding way.
George helped me out with this project with his feedback and has lots of knowledge in computer engineering among with many more and as shown
me that it is okay to ask questions. And Isaias who is always willing to show me the ins and outs of the news station, showcase his interests, display how to be proactive and clean while working and keeping me on my toes.
Without them I wouldn't have an amazing time learning and experiencing, they help showed me that everyone is in it together and that your
not out of place. Now its showtime!</p>

<h2>About this Project</h2>
<p>This was made by Me (@wildmeta5) as a project given by Lee as previously when there was new product that they ordered they would have to put
the information of it in google sheets or exel and organize it later or have it scattered around which can become a problem or extra uneeded
work so this web app what was developed using WAMP is to help that by keeping it into a database with mysql and running with apache. All the pages used php and html to work and format along with
used css for styling the pages. There is also a OCR (Tesseract OCR) used to extract text from photos, which for this project is used to scan the product lable and grab
the name and/or serial number to better speed up and make it easier on inputting new products.</p>
<h2>How to get it started</h2>
<p>This program uses <a href="https://wampserver.aviatechno.net/?lang=en=">WAMP</a> and <a href="https://tesseractocr.org/">TESSERACT OCR</a></p> to make it function. So be sure to install those into their default destination.
<ol>
  <li>WAMP Changes<ul>
      <li>Add a invProject folder into "C:\wamp64\www\"</li>
      <li>Input the files from this github into "C:\wamp64\www\invProject"</li>
      <li>Once those are set up turn on WAMP and access it through the hidden icon. (If it red be sure to troubleshoot it or try left clicking and then pressing start all service) </li>
        <li>Check that Apache, php, mysql are up to date. </li>
        <li>right click on the wamp icon navegate to wamp settings and on the risky section checkmark "allow links on project homepage" (this is to let you easly access the invProject on local host)</li>
        <li>In the php section access the php.ini file and change these secetions:</li>
        <li>upload_max_filesize = 20M</li>
        <li>max_file_uploads = 20</li>
        <li>post_max_size = 50M</li>
        <li>max_execution_time = 300</li>
        <p>Once those have been changed save the file and restart wamp.</p>
  </ul></li>
  <li>Tesseract OCR <ul>
      <li>Download it with its default setting</li>
      <li>Optional: double check it has been downloaded by using command prompt and putting: "C:\Program Files\Tesseract-OCR\tesseract.exe" --version</li>
    <p>After that you are already setup!</p>
  </ul></li>
</ol>
<p>Once they have been set up You can access it with localhost on the laptop/PC that is being used.</p>
<p>Since its a local host to run it on mobile and tablet (or other pc) simplest way is the change httpd-vhosts.conf file that is access through apache section and Add "Require Ip [IPv4 Ip of laptop or pc] below Require Local</p>
<p>What it does is that inputting the IP address on mobile or tablet will have access to the local host but it only works if the devises are on the same wifi.</p>
<p>A temporary way to make it work without being connect to the same wifi is by using <a href="https://ngrok.com/">ngrok</a> to do that is to create an account and download it (follow the link for more detail) once downloaded follow the steps given on the ngrok website in the share localhost, if not given open ngrok input the authtoken (ngrok config add-authtoken [authtoken here]) and put ngrok http 80 [https link], it will forward the link to the https and now you can access the localhost not on the same wifi. Last thing is that when access the project it has to be with https://[https link that was forward]/invProject/project.php and ta-da!</p>

<h2>Strech Feature I would like to work on later...</h2>
<p>Other things to add that is planned is a sperate area where instead of product inventory it is with vehicle's at the station and a log in to separate engineering and media group to look at only the products or only look at only the vehicles and an admin-like that can access both.</p>
<p>-[] Vehicle Database & basic Vehicle screen</p>
<p>-[] Create a working log in screen</p>
<p>-[] update vehicle screen</p>
