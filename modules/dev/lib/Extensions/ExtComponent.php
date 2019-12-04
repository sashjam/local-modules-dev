<?php

namespace Dev\Extensions;

use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);


class ExtComponent extends \CBitrixComponent
{
	/**
	 * Bitrix vars
	 *
	 * @var ExtComponent $this
	 * @var array        $arParams
	 * @var array        $arResult
	 * @var string       $componentPath
	 * @var string       $componentName
	 * @var string       $componentTemplate
	 *
	 * @var string       $parentComponentPath
	 * @var string       $parentComponentName
	 * @var string       $parentComponentTemplate
	 *
	 * @var CDatabase    $DB
	 * @var CUser        $USER
	 * @var CMain        $APPLICATION
	 */

	/**
	 * @var
	 */
	protected $params;

	/**
	 *
	 */
	protected $templateName = null;

	protected $modeTemplate = true;
	/** @var  $App $APPLICATION */

	protected $debugModeOn = false;
	protected $ajaxDebugModeOn = false;

	protected $currentAction;

	protected $sefMode = false;
	protected $sefPattern = '#[^\/\?]+#i';

	protected $jsPlugins = [];




	public function setSef($pattern)
	{
		if (!empty($pattern)) {
			if (!is_bool($pattern)) {
				$this->sefPattern = $pattern;
				$this->sefMode = true;
			} else {
				$this->sefMode = true;
			}
		}
	}


	protected function initParams()
	{
		global $APPLICATION;
		global $USER;

		//$this->User = $USER;
		//$this->App = $APPLICATION;

		parse_str($APPLICATION->GetCurParam(), $this->params['PAGE']);

		if (!empty($this->params['PAGE']['request']) && $this->sefMode) {
			preg_match_all($this->sefPattern, $this->params['PAGE']['request'], $matches);
			$this->arParams['SEF'] = $matches[0];

		}

		$this->params['IS_AJAX'] = $this->request->isAjaxRequest();
		$this->params['QUERY']['POST'] = $this->request->getPostList()->toArray();
		$this->params['QUERY']['GET'] = $this->request->getQueryList()->toArray();
		$this->params['COOKIE'] = $this->request->getCookieList();
		$this->params['SESSION'] = $_SESSION;
		$this->params['COMPONENT'] = $this->arParams;


		$all = array_merge(
			is_array($this->params['PAGE']) ? $this->params['PAGE'] : [],
			is_array($this->params['QUERY']['POST']) ? $this->params['QUERY']['POST'] : [],
			is_array($this->params['QUERY']['GET']) ? $this->params['QUERY']['GET'] : [],
			is_array($this->params['COMPONENT']) ? $this->params['COMPONENT'] : []
		);
		$this->params['ALL'] = $all;

		if (isset($this->params['COMPONENT_PARAMS']['TEMPLATE_MODE']))
			$this->setTemplateMode($this->params['COMPONENT_PARAMS']['TEMPLATE_MODE']);
	}

	public function addJsPlugins($plugins)
	{
		if (is_array($plugins)) {
			foreach ($plugins as $plugin) {
				$this->jsPlugins[] = $plugin;
			}
		} elseif (is_array(explode(',', $plugins))) {
			$plugins = explode(',', $plugins);
			foreach ($plugins as $plugin) {
				$this->jsPlugins[] = trim($plugin);
			}
		} else {
			$this->jsPlugins[] = $plugins;
		}

	}

	private function setTemplateMode($mode)
	{
		$this->modeTemplate = $mode;
	}

	private function getMode()
	{
		return $this->modeTemplate;
	}

	private function includeJS()
	{
		if (!empty($this->jsPlugins) && count($this->jsPlugins) > 0) {

			\CJSCore::Init($this->jsPlugins);

		}
	}

	public function executeComponent()
	{

		global $APPLICATION;
		global $USER;

		$this->componentSetup();
		$this->initParams();

		$this->beforeAction();
		$this->actionRun();
		$this->afterAction();

		if ($this->params['IS_AJAX'] && empty($GLOBALS['IGNORE_AJAX']))
			$this->sendAjaxResponse();

	}

	public function componentSetup()
	{
		$this->debugVars = [
			'PARAMS' => &$this->params,
			'RESULT' => &$this->arResult,
		];

		$this->debugModeOn = false;
		$this->ajaxDebugModeOn = false;
		$this->setup();
	}

	public function setup()
	{
	}

	public function beforeAction()
	{
		global $USER;
		if (($this->debugModeOn && !$this->params['IS_AJAX']) || ($this->ajaxDebugModeOn && $this->params['IS_AJAX'])) {
			if ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin()))
				echo '<code>ComponentName: ' . $this->getName() . '</code ><br>';
		}
	}

	protected function xStylesAdd()
	{
		if (!empty($this->arParams['XSTYLES']) && ($this->arParams['XSTYLES'] == true || is_string($this->arParams['XSTYLES']))) {

			if (is_string($this->arParams['XSTYLES']))
				$name = $this->arParams['XSTYLES'];
			else
				$name = 'xstyles';


			$css = $this->getPath() . '/templates/' . $this->getTemplateName() . '/' . $name . '.css';
			$js = $this->getPath() . '/templates/' . $this->getTemplateName() . '/' . $name . '.js';
			$ext_name = 'ext_' . md5($this->getTemplateName() . '.' . $name);
			$ext_real_name = $this->getName() . '-' . $this->getTemplateName() . '-' . $name;
			if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $css) == true) {
				$warning = 'console.log("Component `' . $ext_real_name . '` msg: try Init xstyles but CSS FILE is not exist!!!");';

				if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $js) === true || md5(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $js)) != $warning) {
					file_put_contents($_SERVER['DOCUMENT_ROOT'] . $js, $warning);
				}


				\CJSCore::RegisterExt(
					$ext_name,
					[
						'js' => [$js],


					]
				);


			} else {
				$success_Msg = 'console.log("Component `' . $ext_real_name . '` msg:Init xstyles");';

				if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $js) === true || md5(file_get_contents($_SERVER['DOCUMENT_ROOT'] . $js)) != $success_Msg) {
					file_put_contents($_SERVER['DOCUMENT_ROOT'] . $js, $success_Msg);
				}

				\CJSCore::RegisterExt(
					$ext_name,
					[
						'js'  => [$js],
						'css' => [$css],

					]

				);

			}
			\CJSCore::Init($ext_name);

		}

	}

	public function afterAction()
	{
		global $USER;

		$this->xStylesAdd();
		if ($this->debugModeOn && !$this->params['IS_AJAX'] && ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin()))) {
			echo '<div style="max-width:98%;margin-left:1%;overflow:auto;margin-top:20px;background:rgba(20,200,20,.1);display:block;padding:20px;border:1px dashed #333;border-radius:5px;">';
			echo '<pre>' . $this->getName() . ':' . $this->getTemplateName();
			if ($this->getParent())
				echo ', Parent - ' . $this->getParent()->getName() . ':' . $this->getParent()->getTemplateName();
			echo '</pre>';
		}

		if ($this->debugModeOn && !$this->params['IS_AJAX'] && ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin()))) {
			$this->getDebugVarLayout();
			echo '</div>';
		}
		if ($this->ajaxDebugModeOn && $this->params['IS_AJAX']) {
			$this->getDebugVarLayout();
		}
	}

	public function actionDefault()
	{
	}

	public function detectAction($action = '')
	{
		global $USER;

		if (!empty($this->params['COMPONENT']['action']))
			$action = $this->params['COMPONENT']['action'];
		if (!empty($this->params['COMPONENT']['ACTION']))
			$action = $this->params['COMPONENT']['ACTION'];
		if (empty($action)) {
			if (!empty($this->params['PAGE']['action']))
				$action = $this->params['PAGE']['action'];
			if (!empty($this->params['PAGE']['ACTION']))
				$action = $this->params['PAGE']['ACTION'];

			if (!empty($this->params['QUERY']['GET']['action']))
				$action = $this->params['QUERY']['GET']['action'];
			if (!empty($this->params['QUERY']['GET']['ACTION']))
				$action = $this->params['QUERY']['GET']['ACTION'];

			if (!empty($this->params['QUERY']['POST']['action']))
				$action = $this->params['QUERY']['POST']['action'];
			if (!empty($this->params['QUERY']['POST']['ACTION']))
				$action = $this->params['QUERY']['POST']['ACTION'];
		}

		$this->currentAction = $action;
	}

	public function actionRun()
	{
		global $APPLICATION, $USER;

		$action = '';
		if (empty($action) && !$this->currentAction) {
			$this->detectAction($action);
		}

		$action = trim($this->currentAction);

		$actionMethod = !empty($action) ? 'action' . mb_convert_case($action, MB_CASE_TITLE, 'UTF-8') : '';

		$actionMethod = str_ireplace(['-', '_'], '', $actionMethod);

		if (($this->debugModeOn && !$this->params['IS_AJAX']) || ($this->ajaxDebugModeOn && $this->params['IS_AJAX']))
			if ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin()))
				echo '<code>DetectedActionMethod: ' . $actionMethod . '</code><br>';

		if (!empty($action) && method_exists($this, $actionMethod)) {
			if (($this->debugModeOn && !$this->params['IS_AJAX']) || ($this->ajaxDebugModeOn && $this->params['IS_AJAX'])) {
				if ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin())) {
					echo '<code>RunActionMethod: ' . $actionMethod . '</code><br>';
					echo '<code>SetTemplate: ' . $action . '</code><br>';
				}

			}
			$this->$actionMethod();

			$this->templateName = $action;


		} else if (empty($action) && method_exists($this, 'actionIndex')) {
			if (($this->debugModeOn && !$this->params['IS_AJAX']) || ($this->ajaxDebugModeOn && $this->params['IS_AJAX'])) {
				if ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin())) {
					echo '<code>RunActionMethod: actionIndex</code><br>';
					echo '<code>SetTemplate: index</code><br>';
				}

			}
			$this->actionIndex();
			$this->templateName = 'index';

		} else if (empty($action)) {
			if (($this->debugModeOn && !$this->params['IS_AJAX']) || ($this->ajaxDebugModeOn && $this->params['IS_AJAX'])) {
				if ($this->debugForAll === true || ($USER->IsAuthorized() && $USER->IsAdmin())) {
					echo '<code>RunActionMethod: actionDefault</code><br>';
					echo '<code>SetTemplate: template</code><br>';
				}

			}
			$this->actionDefault();
			$this->templateName = 'template';
		} else if (!empty($action)) {
			$this->actionNotfound();
		}

		if (isset($this->changedTemplate))
			$this->templateName = $this->changedTemplate;


		if ($this->getMode() == true) {
			$this->includeJS();
			$this->includeComponentTemplate($this->templateName);
		} else {
			$this->includeJS();
			$this->includeComponentTemplate('');
		}

	}

	public function actionNotfound()
	{
		global $APPLICATION;
		\CHTTP::SetStatus("404 Not Found");
		@define("ERROR_404", "Y");
//        die();
	}

	public function sendAjaxResponse($html_mode = false)
	{
		global $USER;
		if (!$html_mode) {
			$this->App->RestartBuffer();
			header('Content-Type: application/json');

			echo json_encode($this->arResult);
			die();
		}
	}

	public function arrayToHtml($array, $key = null)
	{

		static $hashCounter = 1;
		$html = '<ul>';
		foreach ($array as $key => $value) {
			$id = 'h_' . md5($this->getName()) . '_' . ($hashCounter++);
			if (is_array($value)) {

				$html .= '<li><input type="checkbox" id="hash' . $id . '"><label for="hash' . $id . '">' . (empty($key) ? '0' : $key) . ' Array(' . count($value) . ')</label> ' . $this->arrayToHtml($value) . '</li>';

			} else {
				$html .= '<li><input type="checkbox"  id="hash' . $id . '"><label for="hash' . $id . '"> ' . (empty($key) ? '0' : $key) . ' (' . gettype($value) . ')</label> <div>' . (gettype($value) == 'object' ? '<pre>' : '') . print_r($value, true) . (gettype($value) == 'Object' ? '</pre>' : '') . '</div></li>';
			}
		}
		$html .= '</ul>';
		return $html;

	}

	public function getDebugVarLayout()
	{
		if (!empty($this->debugVars)) {
			foreach ($this->debugVars as $varName => $var) {
				$hash = 'var' . $varName;
				$layout = '<p><b>' . $varName . '</b></p><div class="data-structure">' . $this->arrayToHtml($var) . '</div>';
				if (empty(trim($this->debugVarsStyle)))
					$style = '<style>.data-structure{display:inline-block;max-width:100%;overflow:hidden;max-height:initial}.data-structure input+label+ul, .data-structure input+label+div{display:none}.data-structure input:checked+label+ul, .data-structure input:checked+label+div{display:block}.data-structure input{display:none}.data-structure ul{margin-left:20px;list-style:none}.data-structure label{position:relative}.data-structure label:before{content:"+";position:absolute;left:-20px;border:1px solid lightgrey;width:17px;height:17px;top:4px;line-height:14px;padding-left:3px;border-radius:3px}.data- input:checked + label:before{content:"-";position:absolute;left:-20px;padding-left:5px;line-height:14px}.data-structure label{position: relative;margin-bottom: 0;}.data-structure input:checked+label+div{display: block;margin-bottom: 5px;position: relative;margin-left:40px;background:lightgrey;padding-left:10px;border-radius:3px;position:relative;}.data-structure input:checked+label+div:before{content:"";position:absolute;width:20px;height:13px;border-left:1px solid lightgrey;border-bottom:1px solid lightgrey;left:-20px;}.data-structure pre{max-width: 100%;text-overflow: ellipsis;}</style>';
				echo $layout . PHP_EOL . $style;
			}
		}
	}

}