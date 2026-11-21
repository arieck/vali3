Open Validation Server v3 is a free online service for validating IGC files, 
used by the FAI CIVL World XC Online Contest and many other national and international flying contests. 

Prerequisites, recommended Installation:
Install Apache2 + Perl + PHP on Windows 64bit platform

<b>Q:</b> What's the difference to Open Validation Server v2 Version ?<br>
A: This Version here (v3) contains also an simple PHP interface additional to Perl CGI.

<b>Q:</b> Which version to choose if you want to implement your own Server ?<br>
A: If you want to run just your own background service, lets say for LeonardoXC, then you are fine with vali2 from github.
If you want to host the same public web interface like seen on vali.fai-civl.org, then you need a clone of vali3 from github,
and modify the content to your needs.

<b>Q:</b> Can I create a pull request for changes here at GitHub ? For example for my own vali exe binary ?<br>
A: No. Sorry. But you can clone the project and create your web service.
