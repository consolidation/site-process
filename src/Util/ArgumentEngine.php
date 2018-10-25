<?php
namespace Consolidation\SiteProcess\Util;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;

class ArgumentEngine
{
    public function selectArgs(AliasRecord $siteAlias, $args, $options = [])
    {
            $args = $this->appendOptions($args, $options);
            $result = $this->sshWrap($siteAlias, $args);
            // @todo fix.
            // $result = $this->interpolate($siteAlias, $result);

            return $result;
    }

    protected function appendOptions($result, $options)
    {
        foreach ($options as $option => $value) {
            if ($value === true) {
                $result[] = "--$option";
            }
            elseif ($value === false) {
                // Ignore this option.
            }
            else {
                $result[] = "--{$option}={$value}";
            }
        }

          return $result;
    }

    protected function sshWrap(AliasRecord $siteAlias, $args)
    {
        if (!$siteAlias->isRemote()) {
            return $args;
        }

        return [
            'ssh',
            $siteAlias->get('ssh.options', '-o PasswordAuthentication=no'),
            $siteAlias->remoteHostWithUser(),
            implode(' ', $args),
        ];
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
