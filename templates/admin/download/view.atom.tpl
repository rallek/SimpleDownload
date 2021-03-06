{* purpose of this template: downloads atom feed in admin area *}
{verysimpledownloadTemplateHeaders contentType='application/atom+xml'}<?xml version="1.0" encoding="{charset assign='charset'}{if $charset eq 'ISO-8859-15'}ISO-8859-1{else}{$charset}{/if}" ?>
<feed xmlns="http://www.w3.org/2005/Atom">
{gt text='Latest downloads' assign='channelTitle'}
{gt text='A direct feed showing the list of downloads' assign='channelDesc'}
    <title type="text">{$channelTitle}</title>
    <subtitle type="text">{$channelDesc} - {$modvars.ZConfig.slogan}</subtitle>
    <author>
        <name>{$modvars.ZConfig.sitename}</name>
    </author>
{assign var='numItems' value=$items|@count}
{if $numItems}
{capture assign='uniqueID'}tag:{$baseurl|replace:'http://':''|replace:'/':''},{$items[0].createdDate|dateformat|default:$smarty.now|dateformat:'%Y-%m-%d'}:{modurl modname='VerySimpleDownload' type='admin' func='display' ot='download' id=$items[0].id}{/capture}
    <id>{$uniqueID}</id>
    <updated>{$items[0].updatedDate|default:$smarty.now|dateformat:'%Y-%m-%dT%H:%M:%SZ'}</updated>
{/if}
    <link rel="alternate" type="text/html" hreflang="{lang}" href="{modurl modname='VerySimpleDownload' type='admin' func='main' fqurl=1}" />
    <link rel="self" type="application/atom+xml" href="{php}echo substr(\System::getBaseURL(), 0, strlen(\System::getBaseURL())-1);{/php}{getcurrenturi}" />
    <rights>Copyright (c) {php}echo date('Y');{/php}, {$baseurl}</rights>

{foreach item='download' from=$items}
    <entry>
        <title type="html">{$download->getTitleFromDisplayPattern()|notifyfilters:'verysimpledownload.filterhook.downloads'}</title>
        <link rel="alternate" type="text/html" href="{modurl modname='VerySimpleDownload' type='admin' func='display' ot='download' id=$download.id fqurl='1'}" />

        {capture assign='uniqueID'}tag:{$baseurl|replace:'http://':''|replace:'/':''},{$download.createdDate|dateformat|default:$smarty.now|dateformat:'%Y-%m-%d'}:{modurl modname='VerySimpleDownload' type='admin' func='display' ot='download' id=$download.id}{/capture}
        <id>{$uniqueID}</id>
        {if isset($download.updatedDate) && $download.updatedDate ne null}
            <updated>{$download.updatedDate|dateformat:'%Y-%m-%dT%H:%M:%SZ'}</updated>
        {/if}
        {if isset($download.createdDate) && $download.createdDate ne null}
            <published>{$download.createdDate|dateformat:'%Y-%m-%dT%H:%M:%SZ'}</published>
        {/if}
        {if isset($download.createdUserId)}
            {usergetvar name='uname' uid=$download.createdUserId assign='cr_uname'}
            {usergetvar name='name' uid=$download.createdUserId assign='cr_name'}
            <author>
               <name>{$cr_name|default:$cr_uname}</name>
               <uri>{usergetvar name='_UYOURHOMEPAGE' uid=$download.createdUserId assign='homepage'}{$homepage|default:'-'}</uri>
               <email>{usergetvar name='email' uid=$download.createdUserId}</email>
            </author>
        {/if}

        <summary type="html">
            <![CDATA[
            {$download.downloadDescription|truncate:150:"&hellip;"|default:'-'}
            ]]>
        </summary>
        <content type="html">
            <![CDATA[
            {$download->getTitleFromDisplayPattern()|replace:'<br>':'<br />'}
            ]]>
        </content>
    </entry>
{/foreach}
</feed>
