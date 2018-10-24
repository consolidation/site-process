<?php
namespace Consolidation\SiteProcess\Util;

use Consolidation\SiteAlias\AliasRecord;
use Symfony\Component\Process\Process;

/**
 * CommandSpecificOptions will examine a site alias record and will look
 * up any program-specific or command-specific options that may apply
 * for the current commandline arguments and options.
 */
class CommandSpecificOptions
{
    public function insertCommandSpecific(AliasRecord $siteAlias, $args, $options)
    {
        $commandSpecific = $this->getCommandSpecific($siteAlias, $args);
        return array_merge($options, $commandSpecific);
    }

    protected function getCommandSpecific(AliasRecord $siteAlias, $args)
    {
        $program = $this->program($args);
        $subcommand = $this->subcommand($args);
        $options = $siteAlias->get("program.$program.command.$subcommand.options");
        $options += $siteAlias->get("program.$program.options", []);

        // What to do with the command-specific options at the root?
        // Maybe assume these are only for Drush.
        if (strpos($program, 'drush') !== false) {
            $options += $siteAlias->get("command.$subcommand.options");
        }
        return $options;
    }

    /**
     * Given an array with program and arguments, return the program.
     *
     * @param array $args Program with args
     * @return string first element of $args
     */
    protected function program($args)
    {
        if (empty($args)) {
            throw new \Exception('No arguments provided to CommandSpecificOptions.');
        }
        return $args[0];
    }

    /**
     * Given an array with program and arguments, presuming that the program
     * takes subcommands (e.g. git, composer, drush, et.al.), then return
     * the argument that is the subcommand.
     *
     * @param array @args Program with args
     * @return string second non-option element of $args
     */
    protected function subcommand($args)
    {
        // Shift off the program name
        array_shift($args);
        foreach ($args as $arg) {
            if (!isOption($arg)) {
                return $arg;
            }
        }
        return '';
    }

    /**
     * Return 'true' if this argument is an option
     *
     * @return bool
     */
    protected function isOption($arg)
    {
        if (empty($arg)) {
            return false;
        }
        return $arg[0] == '-';
    }
}
