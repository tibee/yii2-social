<?php

/**
 * @copyright Copyright &copy; Kartik Visweswaran, Krajee.com, 2014
 * @package yii2-social
 * @version 1.3.0
 */

namespace kartik\social;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

/**
 * Widget to render various Github buttons. Based on the
 * [unofficial Github Buttons](https://buttons.github.io/).
 *
 * Usage:
 * ```
 * echo GithubXPlugin::widget([
 *     'type' => GithubPlugin::WATCH,
 *     'user' => 'GITHUB_USER',
 *     'repo' => 'GITHUB_REPO',
 *     'settings' => ['data-style'=>'mega']
 * ]);
 * ```
 *
 * @see https://github.com/ntkme/github-buttons
 *
 * @author Kartik Visweswaran <kartikv2@gmail.com>
 * @since 1.0
 */
class GithubXPlugin extends Widget
{
    const WATCH = 'watch';
    const STAR = 'star';
    const FORK = 'fork';
    const ISSUE = 'issue';
    const DOWNLOAD = 'download';
    const FOLLOW = 'follow';

    /**
     * @var string the type of button. One of 'watch', 'fork', 'follow'. This is mandatory.
     */
    public $type;

    /**
     * @var string the Github user name that owns the repo. This is mandatory.
     */
    public $user;

    /**
     * @var string the Github repository name. This is mandatory for all buttons except FOLLOW.
     */
    public $repo;

    /**
     * @var bool whether to show the count. Defaults to `true`.
     */
    public $showCount = true;

    /**
     * @var string the button label to display. If not set it will be autogenerated based on
     * the button type.
     */
    public $label;

    /**
     * @var array the social plugin settings. The following attributes are recognized:
     * - href: GitHub link for the button.
     * - data-style: controls the size of the button one of `default` or `mega`.
     * - data-icon: string, the octicon for the button. It will be autogenerated if not set.
     *   All available icons can be found at [Octicons](https://octicons.github.com/).
     * - data-count-href: GitHub link for the count. It defaults to `href` value (generated from
     *   `repo` and `user` name). Relative url will be relative to `href` value. It will
     *    be autogenerated if not set.
     * - data-count-api: string, GitHub API endpoint for the count. It will be autogenerated if
     *   not set.
     */
    public $settings = [];

    /**
     * @var string the HTML attributes for the button
     */
    public $options = [];

    /**
     * @var array the valid plugins
     */
    protected $validPlugins = [
        self::WATCH,
        self::STAR,
        self::FORK,
        self::ISSUE,
        self::DOWNLOAD,
        self::FOLLOW
    ];

    /**
     * Initialize the widget
     *
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        $this->setConfig('githubX');
        if (empty($this->user)) {
            throw new InvalidConfigException("The GitHub 'user' must be set.");
        }
        if (empty($this->repo) && $this->type !== self::FOLLOW) {
            throw new InvalidConfigException("The GitHub 'repository' has not been set.");
        }
        if (empty($this->type)) {
            throw new InvalidConfigException("The GitHub button 'type' has not been set.");
        }
        if (!isset($this->noscript)) {
            $this->noscript = Yii::t('kvsocial',
                'Please enable JavaScript on your browser to view the Facebook {pluginName} plugin correctly on this site.',
                ['pluginName' => Yii::t('kvsocial', str_replace('fb-', '', $this->type))]
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        Html::addCssClass($this->options, 'github-button');
        Html::addCssStyle($this->options, 'padding:0 5px');
        echo $this->renderButton();
        $view = $this->getView();
        $view->registerJsFile('https://buttons.github.io/buttons.js', [
            'id' => 'github-bjs',
            'async' => true,
            'defer' => true
        ]);
    }

    /**
     * Gets the default button configurations settings
     *
     * @return array
     */
    public function getDefaultSetting()
    {
        $repodir = $this->user . '/' . $this->repo;
        $href = "https://github.com/{$repodir}";
        $api = "/repos/{$repodir}#";
        switch ($this->type) {
            case self::WATCH:
                return [
                    "href" => $href,
                    "data-icon" => "octicon-eye",
                    "data-count-api" => "{$api}subscribers_count",
                    "data-count-href" => "/{$repodir}/watchers",
                    "label" => Yii::t('kvsocial', 'Watch')
                ];
            case self::STAR:
                return [
                    "href" => $href,
                    "data-icon" => "octicon-star",
                    "data-count-api" => "{$api}stargazers_count",
                    "data-count-href" => "/{$repodir}/stargazers",
                    "label" => Yii::t('kvsocial', 'Star')
                ];
            case self::FORK:
                return [
                    "href" => $href . '/fork',
                    "data-icon" => "octicon-git-branch",
                    "data-count-api" => "{$api}forks_count",
                    "data-count-href" => "{$href}/{$repodir}/network",
                    "label" => Yii::t('kvsocial', 'Fork')
                ];
            case self::ISSUE:
                return [
                    "href" => $href . "/issues",
                    "data-icon" => "octicon-issue-opened",
                    "data-count-api" => "{$api}open_issues_count",
                    "label" => Yii::t('kvsocial', 'Issue')
                ];
            case self::DOWNLOAD:
                return [
                    "href" => $href . "/archive/master.zip",
                    "data-icon" => "octicon-cloud-download",
                    "label" => Yii::t('kvsocial', 'Download')
                ];
            case self::FOLLOW:
                return [
                    "href" => "https://github.com/{$this->user}",
                    "data-icon" => "octicon-mark-github",
                    "data-count-api" => "/users/{$this->user}#followers",
                    "data-count-href" => "/{$this->user}/followers",
                    "label" => Yii::t('kvsocial', 'Follow') . " @{$this->user}"
                ];
            default:
                return [];
        }
    }

    /**
     * Renders the button
     *
     * @return string
     */
    public function renderButton()
    {
        $setting = $this->getDefaultSetting();
        $label = empty($this->label) ? ArrayHelper::remove($setting, 'label', '') : $this->label;
        if (!$this->showCount) {
            unset($setting['data-count-api'], $setting['data-count-href']);
        }
        $setting += $this->settings + $this->options;
        return Html::tag('a', $label, $setting);
    }
}