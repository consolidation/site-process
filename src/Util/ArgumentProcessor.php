<?php
namespace Consolidation\SiteProcess\Util;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;
use Consolidation\Config\Util\Interpolator;

/**
 * ArgumentProcessor takes a set of arguments and options from the caller
 * and processes them with the provided site alias to produce a final
 * executable command that will run either locally or on a remote system,
 * as applicable.
 */
class ArgumentProcessor
{
    /**
     * selectArgs selects the appropriate set of arguments for the command
     * to be executed and orders them as needed.
     *
     * @param AliasRecord $siteAlias Description of
     * @param array $args Command and arguments to execute (source)
     * @param array $options key / value pair of option and value in include
     *   in final arguments
     * @param array $optionsPassedAsArgs key / value pair of option and value
     *   to include in final arguments after the '--' argument.
     * @return array Command and arguments to execute
     */
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

    /**
     * findArgSeparator finds the "--" argument in the provided arguments list,
     * if present, and returns the arguments in three sets.
     *
     * @return array of three arrays, leading, "--" and trailing
     */
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

    /**
     * convertOptions takes an associative array of options (key / value) and
     * converts it to an array of strings in the form --key=value.
     *
     * @param array $options in key => value form
     * @return array options in --option=value form
     */
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

    /**
     * wrapWithTransport examines the provided site alias; if it is a local
     * alias, then the provided arguments are returned unmodified. If the
     * alias points at a remote system, though, then the arguments are
     * escaped and wrapped in an appropriate ssh command.
     *
     * @param AliasRecord $siteAlias alias record of target site.
     * @param array $args arguments provided by caller.
     * @return array command and arguments to execute.
     */
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

    /**
     * interpolate examines each of the arguments in the provided argument list
     * and replaces any token found therein with the value for that key as
     * pulled from the given site alias.
     *
     * Example: "git -C {{root}} status"
     *
     * The token "{{root}}" will be converted to a value via $siteAlias->get('root').
     * The result will replace the token.
     *
     * It is possible to use dot notation in the keys to access nested elements
     * within the site alias record.
     *
     * @param AliasRecord $siteAlias
     * @param type $args
     * @return type
     */
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
