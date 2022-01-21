<?php

namespace OpenSourceSupportReport\Cli;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\Filter\FilterOutputData;
use Consolidation\Filter\LogicalOpFactory;
use Consolidation\OutputFormatters\Options\FormatterOptions;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Robo\Common\ConfigAwareTrait;
use Robo\Contract\ConfigAwareInterface;
use Consolidation\AnnotatedCommand\CommandError;
use Hubph\HubphAPI;
use Hubph\VersionIdentifiers;
use Hubph\PullRequests;
use Hubph\Git\WorkingCopy;
use Hubph\Git\Remote;
use OpenSourceSupportReport\Util\SupportLevel;
use OpenSourceSupportReport\Util\ProjectUpdate;

class OrgCommands extends \Robo\Tasks implements ConfigAwareInterface, LoggerAwareInterface
{
    use ConfigAwareTrait;
    use LoggerAwareTrait;

    /**
     * @command org:analyze
     * @param $org The org to list
     * @filter-output
     * @field-labels
     *   url: Url
     *   id: ID
     *   owner: Owner
     *   name: Shortname
     *   full_name: Name
     *   private: Private
     *   fork: Fork
     *   created_at: Created
     *   updated_at: Updated
     *   pushed_at: Pushed
     *   git_url: Git URL
     *   ssh_url: SSH URL
     *   svn_url: SVN URL
     *   homepage: Homepage
     *   size: Size
     *   stargazers_count: Stargazers
     *   watchers_count: Watchers
     *   language: Language
     *   has_issues: Has Issues
     *   has_projects: Has Projects
     *   has_downloads: Has Downloads
     *   has_wiki: Has Wiki
     *   has_pages: Has Pages
     *   forks_count: Forks
     *   archived: Archived
     *   disabled: Disabled
     *   open_issues_count: Open Issues
     *   default_branch: Default Branch
     *   license: License
     *   permissions: Permissions
     *   codeowners: Code Owners
     *   owners_src: Owners Source
     *   ownerTeam: Owning Team
     *   support_level: Support Level
     * @default-fields full_name,codeowners,owners_src,support_level
     * @default-string-field full_name
     *
     * @return Consolidation\OutputFormatters\StructuredData\RowsOfFields
     */
    public function orgAnalyze($org, $options = ['as' => 'default', 'format' => 'table'])
    {
        $api = $this->api($options['as']);
        $pager = $api->resultPager();

        $repoApi = $api->gitHubAPI()->api('organization');
        $repos = $pager->fetchAll($repoApi, 'repositories', [$org]);

        // Remove archived repositories from consideration
        $repos = array_filter($repos, function ($repo) {
            return empty($repo['archived']);
        });

        // TEMPORARY: only do the first 20
        // $repos = array_splice($repos, 0, 20);

        // Add CODEOWNER information to repository data
        $reposResult = [];
        foreach ($repos as $key => $repo) {
            $resultKey = $repo['id'];

            list($codeowners, $ownerSource) = $this->guessCodeowners($api, $org, $repo['name']);

            $repo['codeowners'] = $codeowners;
            $repo['owners_src'] = $ownerSource;

            if (empty($codeowners)) {
                $repo['ownerTeam'] = 'n/a';
            } else {
                $repo['ownerTeam'] = str_replace("@$org/", "", $codeowners[0]);
            }

            try {
                $data = $api->gitHubAPI()->api('repo')->contents()->show($org, $repo['name'], 'README.md');
                if (!empty($data['content'])) {
                    $content = base64_decode($data['content']);
                    $repo['support_level'] = SupportLevel::getSupportLevelsFromContent($content, true);
                }
            } catch (\Exception $e) {
            }

            $reposResult[$resultKey] = $repo;
        }

        $data = new \Consolidation\OutputFormatters\StructuredData\RowsOfFields($reposResult);
        $this->addTableRenderFunction($data);

        return $data;
    }

    protected function api($as = 'default')
    {
        $api = new HubphAPI($this->getConfig());
        $api->setAs($as);

        return $api;
    }
}
