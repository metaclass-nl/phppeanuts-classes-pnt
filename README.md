<H2>Release notes</H2>
<P>Version 2.4.0.alpha<BR>
<BR>
This library only contains the pnt classes. To use it install phppeanuts-skeleton and follow the instructions in its Readme.md</P>
<BR>
Documentation: https://www.phppeanuts.org/
<BR>
<H3>What's new</H3>

<P>Since 2.3.0</P>
<UL>
	<li>Bugs fixed:
	<ul>
      <li>E_NOTCE missing key: SqlFilterTest</li>
      <li>ValueValidator not yet included: PntErrorHandler</li>
	</ul>
	<li>Other improvements:
	<ul>
		<li>No longer passes null to string functions</li>
        <li>Either declares all member variables or #[\AllowDynamicProperties] </li>
		<li>added conveniece functions substr and strlen to PntGen</li>
		<li>tested with PHP 7.2.24, 8.0.2, 8.1.17, 8.2.4</li>
		<Ll>more details and small changes see <a href="doc/changes.txt">doc/changes.txt</a></li>
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
	<li>The AGPL license requires you to license the source of applications using this version
	of phpPeanuts under the AGPL license. 
</OL>
