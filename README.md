<H2>Release notes</H2>
<P>Version 2.3.0<BR>
<BR>
This library only contains the pnt classes. To use it install phppeanuts-skeleton and follow the instructions in its Readme.md</P>
<BR>
Documentation: https://www.phppeanuts.org/
<BR>
<H3>What's new</H3>

<P>Since 2.2.0</P>
<UL>
	<li>Bugs fixed:
	<ul>
		<li>validationWarning for integer 0 too short
		<li>PntStringConverter::sanitizeHtml returned an empty result
		<li>PntSqlFilter::addParamsTo no longer adds a parameter if no comparator
		<li>Special signs in image urls caused malformed xml http response
        <li>Incompatibility with MySQL 5.7.18
        <li>PntPdoDao::param now returns : in front of placeholder parameter
	</ul>
	<li>Other improvements:
	<ul>
		<li>PntHttpRequest now allows windows file paths and Http 2.0
		<li>Protects file system against potential buffer overflows
		<li>PntSite adapted to work from the command line
		<li>tested with PHP 5.5.9, 5.6.11, 7.0.1, 7.1, 7.2.24, 7.4.13, 8.0.2
		<LI>several small changes, see <a href="doc/changes.txt">doc/changes.txt</a>.
	</ul>
</UL>

<H3>Remarks for upgrading existing applications</H3>
<p>You may change you application to use composer to install and update this library.
See https://github.com/metaclass-nl/phppeanuts-skeleton for an example.<br>
Or you may simply replace the contents of your classes/pnt folder with the contents
of src/pnt from this library</p>
<p>

<H3>Known bugs and limitations</H3>

<OL>
	<li>UTF-8 not supported
	<li>Applications are only protected against cross frame scripting in browsers that support the X-Frame-Options header. 
	<li>The Synchronizer Token Pattern by referrerer tokens is not as strong as by request tokens. (currently
	most frameworks only implement this pattern for actions (called tickets with peanuts)). 
	<li>Though the framework has DAO classes that are successfully used as the database abstraction layer with MySQL
	and SqLite, the use with other databases may require some additional refactoring. Please inform us about eventual
	problems and solutions with the use of other databases. (Known: Oracle versions below 9 do not support standard
	explicit JOIN syntax, but producing JOIN instuctions is not delegated to DAO objects and can not be easily refactored
	to do so.)
	<li>The AGPL license requires you to make the source of applications using this version
	of phpPeanuts available to any users outside your own organization, and allow them forward
	it to the rest of the world. 
</OL>
