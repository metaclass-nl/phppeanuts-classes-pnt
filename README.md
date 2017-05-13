<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<HTML>

<HEAD>
	<META HTTP-EQUIV="Content-Type" CONTENT="text/html;CHARSET=iso-8859-1">
	<TITLE>phpPeanuts release notes</TITLE>
</HEAD>

<BODY>

<H2>Release notes</H2>
<P>Version 2.2.0 for composer<BR>
<BR>
This library only contains the pnt classes. To use it install phppeanuts-skeleton and follow the instructions in its Readme.md</P>

<P>This version does not include pntUnit and the unit tests. 
<H3>What's new</H3>

<P>Since 2.1.0</P>
	<li>Security improvements:
	<ul>
		<li>Synchronizer Token Pattern for referrer tokens in all urls
		<li>ActionTickets now use hashed random tokens with timeout
		<li>Only uses parameterized queries (may be emulated)
		<li>Parameterized query emulation for old MySql driver
		<li>PntValidationException thrown on invalid request data that should never be produced by applications
	</ul>
	<li>Other improvements:
	<ul>
		<li>Scouting data and Tokens now support the usage of serveral phpPeanuts root folders (baseUrls) on the same (virtual) server
		<li>tested with PHP 5.4.8
		<LI>many small changes, see <A HREF="changes.txt">changes.txt</A> .
	</ul>
</UL>

<H3>Remarks for upgrading existing applications</H3>
<p>See the release notes of the upgrade release you can download from the phpPeanuts website.</p>

<H3>Known bugs and limitations</H3>

<OL>
	<li>Applications are only protected against cross frame scripting in browsers that support the X-Frame-Options header. 
	<li>The Synchronizer Token Pattern by referrerer tokens is not as strong as by request tokens. (currently
	most frameworks only implement this pattern for actions (called tickets with peanuts)). 
	<li>With older versions of PHP and/or MySQL the character set can not be set on the connection in such a way that the 
		quoting functions of MySQL take the character set into account (This is a limitation of PHP and MySql). 
		This may be a problem with UTF-8 and it may 
		have security implications, possibly including SQL injection vurnerabilities. To avoid this requires:<br>
		- MySQL >= 5.0.7 or if you're using MySQL 4, then >= 4.1.13.<br>
		- PntMySqlDao: PHP 5.0.7 or later<br>
		- PntPdoDao: PHP 5.3.6 or later<br>
		- PntMySqliDao (not included in the open source version): PHP 5.0.5 or later.
		Emulated parameterized queries like used by PDO and PntMySqlDao will not protect you from this! (You may configure
		PDO to use native parameterization)
	<li>Though the framework has DAO classes that are successfully used as the database abstraction layer with MySQL
	and SqLite, the use with other databases may require some additional refactoring. Please inform us about eventual
	problems and solutions with the use of other databases. (Known: Oracle versions below 9 do not support standard
	explicit JOIN syntax, but producing JOIN instuctions is not delegated to DAO objects and can not be easily refactored
	to do so.)
	<li>The AGPL license requires you to make the source of applications using this version
	of phpPeanuts available to any users outside your own organization, and allow them forward
	it to the rest of the world. 
</OL>


</BODY>

</HTML>