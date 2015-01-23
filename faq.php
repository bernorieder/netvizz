<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
	<title>index.php</title>

	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

	<link href="facebook.css" rel="stylesheet" type="text/css" />

</head>

<body class="fbbody">

<h1>Frequently Asked Questions</h1><br />


<h2>What is Netvizz?</h2>

Netvizz is a tool that extracts data from different sections of the Facebook platform (personal profile, groups, pages) for research purposes. File outputs
can be easily analyzed in standard software.<br /><br />


<h2>Netvizz askes for permissions to access my data. What is the privacy policy?</h2>

Please consult the privacy policy <a href="privacy.php">here</a>.<br /><br />


<h2>Who develops Netvizz?</h2>

Netvizz is written and maintained by <a href="http://rieder.polsys.net">Bernhard Rieder</a>, Associate Professor in Media Studies at the
<a href="http://www.uva.nl">University of Amsterdam</a> and researcher at the
<a href="https://www.digitalmethods.net" target="_blank">Digital Methods Initiative</a>.
Follow <a href="https://twitter.com/hashtag/netvizz?src=hash" target="_blank">#netvizz</a> or <a href="https://twitter.com/RiederB/" target="_blank">@RiederB</a> for updates on Twitter.<br /><br />


<h2>How can I cite Netvizz?</h2>

This <a href="http://rieder.polsys.net/files/rieder_websci.pdf" target="_blank">paper</a> documents the application:
<i>B. Rieder (2013). Studying Facebook via data extraction: the Netvizz application. In WebSci '13 Proceedings of the 5th Annual ACM Web Science Conference (pp. 346-355). New York: ACM.</i><br /><br />


<h2>Outputs for groups and pages have anonymized user names. Why? Can this be reverted?</h2>

Because Netvizz can be used to extract significant amounts of data from Facebook, I aim at protecting users from political or commercial harm. In anonymized files,
user names are <a href="http://en.wikipedia.org/wiki/Hash_function" target="_blank">hashed</a> and cannot be reverted.<br /><br />


<h2>What kind of outputs does Netvizz produce?</h2>

It creates network files in <a href="http://guess.wikispot.org/The_GUESS_.gdf_format" target="_blank">gdf format</a> (a simple text format that specifies a graph) as well as
statistical files using a <a href="http://en.wikipedia.org/wiki/Tab-separated_values">tab-separated format</a>.<br /><br />

These files can then be analyzed and visualized using graph visualization software such as the powerful and very easy to use <a href="http://gephi.org/" target="_blank">gephi</a>
platform or statistical tools such as R, Excel, SPSS or the interactive visualization software <a href="http://www.rosuda.org/Mondrian/">Mondrian</a>.<br /><br />


<h2>Can I get historical like numbers for the page itself?</h2>

No. The Facebook API does not provide this data.<br /><br />


<h2>I work at a marketing company, can I use Netvizz to do work for my clients?</h2>

No. This tool is built solely for academic and personal research, you may not use it for commercial purposes.<br /><br />


<h2>I don't know how to use netvizz, can you help me?</h2>

Unfortunately, I do not have the spare time to provide any assistance for this app and can therefore not respond to inquiries concerning how to use the app or how
to solve a particular research problem with it.<br /><br />

But there are several tutorials online and there is also a
<a href="http://rieder.polsys.net/files/rieder_websci.pdf" target="_blank">research paper</a> that provides some directions. I also write
about ideas and applications for the tool on my <a href="http://thepoliticsofsystems.net" target="_blank">blog</a>. The interface for
each data module contains a description of file outputs at the bottom of the page. Most importantly, to make sense of the data, a good
understanding of Facebook's basic architecture is required. The <a href="https://developers.facebook.com/docs/graph-api/reference/"  target="_blank">
documentation</a> for Facebook's graph API has comprehensive descriptions of entities and metrics.<br /><br />
If you would like to learn more about this kind of research, you may want to consider joining the Digital Methods Initiative's
<a href="https://wiki.digitalmethods.net/Dmi/DmiSummerSchool" target="_blank">summer</a> or
<a href="https://wiki.digitalmethods.net/Dmi/WinterSchool" target="_blank">winter</a> school, or even enrol in our M.A. program in
<a href="http://mastersofmedia.hum.uva.nl/2013/12/04/call-for-applications-international-m-a-in-new-media/" target="_blank">New Media and Digital Culture</a>.<br /><br />


<h2>The tool does not work!</h2>

Facebook is a complicated contraption and the app regularly breaks for some users in some contexts. Bug reports are very useful, but before reporting a problem,
please read <a href="http://www.chiark.greenend.org.uk/~sgtatham/bugs.html" target="_blank">this</a>. (TL;DR: developers need context to debug a tool, when filing a bug report,
please add the URL of the call, the browser you are using, a screenshot of the interface output, the data files, and a description of how the problem manifests itself)<br/>
Please send bug reports to
<a href="mailto:rieder@uva.nl">rieder@uva.nl</a>.<br /><br />


<h2>Where is Netvizz' source code? Can I have it?</h2>

Because it is very easy to disable anonymization, I am currently not making the source code publicly available.


</body>
</html>