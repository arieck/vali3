Open Validation Server v3 is a free online service for validating IGC files, 
used by the FAI CIVL World XC Online Contest and many other national and international flying contests. 
More details, and a running version at http://vali.fai-civl.org/

Prerequisites, recommended Installation:

Installed Apache2 + Perl + PHP on Windows 32bit or 64bit platform
  for example using xampp on Windows 7 or Windows 2008
  see www.apachefriends.org/en/xampp.html?

More Details at INSTALL.txt and at http://vali.fai-civl.org/

Q: What's the difference to Open Validation Server v2 Version ?<br>
A: This Version here (v3) contains also an simple PHP interface (MVC implementation).
It is the version you will see, when you visit http://vali.fai-civl.org/
V3 implements also a JSON response feature for validation requests, 
and we have added a more nice public web interface for igc file validations.

Q: Which version to choose if you wnat to implement your own ?<br>
A: If you want to run just a background service, lets say for LeonardoXC, then you are fine with vali2 from github.
If you want to host the same public web interface like seen on vali.fai-civl.org seen, then you need a clone of vali3 from github,
and modify the content to your needs.

Open ToDo's at this v3 project:
- Migrating from OpenID 2.0 to OpenID Connect for the Admin Login (current simply disabled).