<?php
namespace Consolidation\SiteProcess\Util;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;
use Consolidation\Config\Util\Interpolator;

class ArgumentProcessor
{
    public function selectArgs(AliasRecord $siteAlias, $args, $options = [], $optionsPassedAsArgs = [])
    {
        // Split args into three arrays separated by the `--`
        list($leadingArgs, $dashDash, $remaingingArgs) = $this->findArgSeparator($args);
        $convertedOptions = $this->convertOptions($options);
        $convertedOptionsPassedAsArgs = $this->convertOptions($optionsPassedAsArgs);

        // If the caller provided options that should be passed as args, then we
        // always need a `--`, whether or not one existed to begin with in $args
        if (!empty($convertedOptionsPassedAsArgs)) {
            $dashDash = ['--'];
        }

        // Combine our separated args in the correct order. $dashDash will
        // always be `['--']` if $optionsPassedAsArgs or $remaingingArgs are
        // not empty, and otherwise will usually be empty.
        $selectedArgs = array_merge(
            $leadingArgs,
            $convertedOptions,
            $dashDash,
            $convertedOptionsPassedAsArgs,
            $remaingingArgs
        );

        // Do any necessary interpolation on the selected arguments.
        $processedArgs = $this->interpolate($siteAlias, $selectedArgs);

        // Wrap the command with 'ssh' or some other transport if this is
        // a remote command; otherwise, leave it as-is.
        return $this->wrapWithTransport($siteAlias, $processedArgs);
    }

    protected function findArgSeparator($args)
    {
        $pos = array_search('--', $args);
        if ($pos === false) {
            return [$args, [], []];
        }

        return [
            array_slice($args, 0, $pos),
            ['--'],
            array_slice($args, $pos + 1),
        ];
    }

    protected function convertOptions($options)
    {
        $result = [];
        foreach ($options as $option => $value) {
            if ($value === true || $value === null) {
                $result[] = "--$option";
            } elseif ($value === false) {
                // Ignore this option.
            } else {
                $result[] = "--{$option}={$value}";
            }
        }

        return $result;
    }

    protected function wrapWithTransport(AliasRecord $siteAlias, $args)
    {
        if (!$siteAlias->isRemote()) {
            return $args;
        }

        // @TODO: Methinks this needs to be escaped for the target system.
        $commandToExecute = implode(' ', $args);

        // Question: How could we support variable transport mechanisms?
        return [
            'ssh',
            $siteAlias->get('ssh.options', '-o PasswordAuthentication=no'),
            $siteAlias->remoteHostWithUser(),
            $commandToExecute,
        ];
    }

    protected function interpolate(AliasRecord $siteAlias, $args)
    {
        $interpolator = new Interpolator();
        return array_map(
            function ($arg) use ($siteAlias, $interpolator) {
                return $interpolator->interpolate($siteAlias, $arg, false);
            },
            $args
        );
    }
}
