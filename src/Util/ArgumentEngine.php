<?php
namespace SiteProcess;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;

class ArgumentEngine
{
    public function selectArgs(AliasRecord $siteAlias, $args, $options = [])
    {
    		$result = $args;

    		$result = $this->appendOptions($result, $options);
    		$result = $this->sshWrap($siteAlias, $result);
    		$result = $this->interpolate($siteAlias, $result);

    		return $result;
    }

    protected function appendOptions($result, $options)
    {
    	  foreach ($options as $option => $value)
    	  {
    	  		// TODO: escape as necessary
    	  		$result[] = "--{$option}={$value}";
    	  }

    	  return $result;
    }

    protected function sshWrap(AliasRecord $siteAlias, $result)
    {
    		// Exit early if not remote
    	  if (!$siteAlias.has('remote')) {
    	  	  return $result;
    	  }

    	  // TODO: Pull out ssh info from site alias and wrap
    	  // commands in $result in an ssh command.

    	  return $result;
    }

    protected function interpolate(AliasRecord $siteAlias, $message)
    {
    		$replacements = $this->replacements($siteAlias, $message);
        return strtr($message, $replacements);
    }

    protected function replacements(AliasRecord $siteAlias, $message, $default = '')
    {
    	  if (!preg_match_all('#{{([a-zA-Z0-9._-]+)}}#', $message, $matches, PREG_SET_ORDER)) {
    	  		return [];
    	  }
    	  $replacements = [];
    	  foreach ($matches as $matchSet) {
    	  	  list($sourceText, $key) = $matchSet;
    	  	  $replacementText = $siteAlias->get($key, $default);
    	  	  if ($replacementText !== null) {
		    	  	  $replacements[$sourceText] = $replacementText;
		    	  }
    	  }
    	  return $replacements;
    }
}
