<h2>test de creation à la volée d'objet et classes EZ</h2>
<h4>NOTICE : <span>({get_type($notice)})</span></h4>
<p>
{if not(is_null($notice))}
	{if or( is_array($notice), is_object($notice) )}
		{$notice|attribute("show",3)}
	{elseif is_boolean($notice)}
		{if eq($notice,true)}true{else}false{/if}
	{else}
		{$notice}
	{/if}
{/if}
</p>

<h4>RESULT : <span>({get_type($result)})</span></h4>
<p>
{if not(is_null($result))}
	{if or( is_array($result), is_object($result) )}
		{$result|attribute("show",3)}
	{elseif is_boolean($result)}
		{if eq($result,true)}true{else}false{/if}
	{else}
		{$result}
	{/if}
{/if}
</p>