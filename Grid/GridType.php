<?php
declare(strict_types=1);
/*
 * This file is part of the Stinger Soft AgGrid package.
 *
 * (c) Oliver Kotte <oliver.kotte@stinger-soft.net>
 * (c) Florian Meyer <florian.meyer@stinger-soft.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StingerSoft\AggridBundle\Grid;

use Doctrine\ORM\QueryBuilder;
use StingerSoft\AggridBundle\Column\ColumnTypeInterface;
use StingerSoft\AggridBundle\StingerSoftAggridBundle;
use StingerSoft\AggridBundle\View\GridView;
use StingerSoft\PhpCommons\Builder\HashCodeBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class GridType extends AbstractGridType {

	public const DATA_MODE_INLINE = 'inline';
	public const DATA_MODE_AJAX = 'ajax';
	public const DATA_MODE_ENTERPRISE = 'enterprise';

	private $licenseKey;

	public function __construct(ParameterBagInterface $parameterBag) {
		if($parameterBag->has(StingerSoftAggridBundle::PARAMETER_LICENSE_KEY)) {
			$this->licenseKey = $parameterBag->get(StingerSoftAggridBundle::PARAMETER_LICENSE_KEY);
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\AggridBundle\Grid\GridTypeInterface::buildGrid()
	 */
	public function getParent(): ?string {
		return null;
	}

	/**
	 *
	 * {@inheritdoc}
	 * @see \StingerSoft\AggridBundle\Grid\GridTypeInterface::buildGrid()
	 */
	public function buildView(GridView $view, GridInterface $grid, array $gridOptions, array $columns): void {
		$this->configureDefaultViewValues($view, $gridOptions, $columns);
		$this->configureAggridViewValues($view, $gridOptions);
		$this->configureStingerViewValues($view, $gridOptions, $columns);
	}

	/**
	 * {@inheritdoc}
	 * @see \StingerSoft\AggridBundle\Grid\GridTypeInterface::configureOptions()
	 */
	public function configureOptions(OptionsResolver $resolver): void {
		$this->configureStingerOptions($resolver);
		$this->configureAggridOptions($resolver);
	}

	private function configureDefaultViewValues(GridView $view, array $gridOptions, array $columns): void {
		$view->vars['id'] = $gridOptions['attr']['id'] = $view->getGridId();
		$view->vars['aggrid_id'] = str_replace('-', '_', $view->vars['id']);
		$view->vars['aggrid_js_id'] = str_replace([' ', '#'], ['_', ''], $view->vars['aggrid_id']);
		$view->vars['stingerSoftAggrid_js_var'] = 'stingerSoftAggrid' . $view->vars['aggrid_js_id'];
		$view->vars['ajax_url'] = $gridOptions['ajax_url'];
		$view->vars['dataMode'] = $gridOptions['dataMode'];
		$gridOptions['attr']['style'] = 'height: ' . $gridOptions['height'];
		$gridOptions['attr']['class'] = 'ag-theme-balham';
		$view->vars['attr'] = $gridOptions['attr'];

	}

	private function configureAggridViewValues(GridView $view, array $gridOptions): void {
		$view->vars['enterpriseLicense'] = $gridOptions['enterpriseLicense'];
		$view->vars['treeData'] = $gridOptions['treeData'];
		$view->vars['sideBar'] = $gridOptions['sideBar'];
		$view->vars['cacheBlockSize'] = $gridOptions['cacheBlockSize'];
		$view->vars['pagination'] = $gridOptions['pagination'];
		$view->vars['paginationPageSize'] = $gridOptions['paginationPageSize'];
		$view->vars['paginationAutoPageSize'] = $gridOptions['paginationAutoPageSize'];
		$view->vars['suppressPaginationPanel'] = $gridOptions['suppressPaginationPanel'];
		$view->vars['icons'] = $gridOptions['icons'];
		$view->vars['suppressCsvExport'] = $gridOptions['suppressCsvExport'];
		$view->vars['suppressExcelExport'] = $gridOptions['suppressExcelExport'];
		$view->vars['rowStyle'] = $gridOptions['rowStyle'];
		$view->vars['getRowStyle'] = $gridOptions['getRowStyle'];
		$view->vars['rowClass'] = $gridOptions['rowClass'];
		$view->vars['getRowClass'] = $gridOptions['getRowClass'];
		$view->vars['rowClassRules'] = $gridOptions['rowClassRules'];
		$view->vars['rowSelection'] = $gridOptions['rowSelection'];
		$view->vars['rowMultiSelectWithClick'] = $gridOptions['rowMultiSelectWithClick'];
		$view->vars['suppressRowClickSelection'] = $gridOptions['suppressRowClickSelection'];
		$view->vars['nativeOptions'] = $gridOptions['nativeOptions'];
	}

	private function configureStingerViewValues(GridView $view, array $gridOptions, array $columns): void {
		$view->vars['translation_domain'] = $gridOptions['translation_domain'];
		$view->vars['total_results_query_builder'] = $gridOptions['total_results_query_builder'];
		$view->vars['default_order_property'] = $gridOptions['default_order_property'];
		$view->vars['default_order_direction'] = $gridOptions['default_order_direction'];
		$view->vars['persistState'] = $gridOptions['persistState'];
		$view->vars['searchEnabled'] = $gridOptions['searchEnabled'];
		$view->vars['paginationDropDown'] = $gridOptions['paginationDropDown'];

		if($gridOptions['versionHash'] === true) {
			$hashing = hash_init('sha256', HASH_HMAC, 'stingersoft-aggrid');
			foreach($columns as $column) {
				hash_update($hashing, (string)$column->getHashCode());
			}
			if($gridOptions['versionHashModifier'] !== null) {
				hash_update($hashing, $gridOptions['versionHashModifier']);
			}
			$gridOptions['versionHash'] = hash_final($hashing);
		}
		$view->vars['versionHash'] = $gridOptions['versionHash'];
	}

	private function configureStingerOptions(OptionsResolver $resolver): void {
		$resolver->setDefault('translation_domain', 'messages');
		$resolver->setAllowedTypes('translation_domain', [
			'string',
			'null',
			'boolean',
		]);
		$resolver->setDefault('total_results_query_builder', null);
		$resolver->setAllowedTypes('total_results_query_builder', ['null', QueryBuilder::class]);

		$resolver->setDefault('default_order_property', 'id');
		$resolver->setAllowedTypes('default_order_property', ['string', 'null']);
		$resolver->setDefault('default_order_direction', 'asc');
		$resolver->setAllowedValues('default_order_direction', ['asc', 'desc']);

		$resolver->setDefault('height', '50vh');

		$resolver->setDefault('hydrateAsObject', true);
		$resolver->setAllowedTypes('hydrateAsObject', [
			'boolean',
		]);

		$resolver->setDefault('persistState', false);
		$resolver->setAllowedTypes('persistState', ['boolean']);

		$resolver->setDefault('searchEnabled', true);
		$resolver->setAllowedTypes('searchEnabled', ['boolean']);

		$resolver->setDefault('paginationDropDown', static function (Options $options, $previousValue) {
			if($previousValue === null && $options['pagination']) {
				return [10, 25, 50, 75, 100, 150, 200, 250, 500];
			}
			return $previousValue;
		});
		$resolver->setAllowedTypes('paginationDropDown', ['null', 'array']);

		$resolver->setDefault('versionHash', static function (Options $options, $previousValue) {
			if($previousValue === null && $options['persistState'] === true) {
				return true;
			}
			return $previousValue;
		});
		$resolver->setAllowedTypes('versionHash', ['bool', 'null', 'string']);

		$resolver->setDefault('versionHashModifier', null);
		$resolver->setAllowedTypes('versionHashModifier', ['null', 'string']);
	}

	private function configureAggridOptions(OptionsResolver $resolver): void {
		$resolver->setDefault('dataMode', self::DATA_MODE_INLINE);
		$resolver->setAllowedValues('dataMode', [
			self::DATA_MODE_INLINE,
			self::DATA_MODE_AJAX,
			self::DATA_MODE_ENTERPRISE,
		]);

		$resolver->setDefault('ajax_url', null);
		$resolver->setAllowedTypes('ajax_url', [
			'string',
			'null',
		]);

		$resolver->setNormalizer('ajax_url', static function (Options $options, $valueToNormalize) {
			if($valueToNormalize === null && $options['dataMode'] !== self::DATA_MODE_INLINE) {
				throw new InvalidOptionsException('When using "dataMode"  with a value of ajax or enterprise you must set "ajax_url"!');
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('ajax_method', 'POST');
		$resolver->setAllowedValues('ajax_method', [
			'GET',
			'POST',
		]);

		$resolver->setDefault('enterpriseLicense', function (Options $options, $previousValue) {
			if($previousValue === null) {
				return $this->licenseKey;
			}
			return $previousValue;
		});
		$resolver->setAllowedTypes('enterpriseLicense', [
			'string',
			'null',
		]);

		$resolver->setDefault('treeData', false);
		$resolver->setAllowedValues('treeData', [
			true,
			false,
		]);
		$resolver->setNormalizer('treeData', static function (Options $options, $value) {
			if($value !== false && !isset($options['enterpriseLicense'])) {
				throw new InvalidArgumentException('treeData is only available in the enterprise edition. Please set a license key!');
			}
			return $value;
		});

		$resolver->setDefault('sideBar', false);
		$resolver->setAllowedValues('sideBar', static function ($valueToCheck) {
			if($valueToCheck === 'columns' || $valueToCheck === 'filters') {
				return true;
			}
			if($valueToCheck === false || $valueToCheck === true) {
				return true;
			}
			if(is_array($valueToCheck)) {
				return true;
			}
			return false;
		});
		$resolver->setNormalizer('sideBar', function (Options $options, $valueToNormalize) {
			if($valueToNormalize !== false && !isset($options['enterpriseLicense'])) {
				throw new InvalidArgumentException('sideBar is only available in the enterprise edition. Please set a license key!');
			}
			if(is_array($valueToNormalize)) {
				return $this->validateSideBarOptions($options, $valueToNormalize);
			}
			return $valueToNormalize;
		});

		$resolver->setDefault('menuTabs', null);
		$resolver->setAllowedTypes('menuTabs', ['null', 'array']);
		$resolver->setNormalizer('menuTabs', static function (Options $options, $value) {
			if($value === null) {
				return $value;
			}
			if(is_array($value)) {
				foreach($value as $item) {
					if(!in_array($item, ColumnTypeInterface::MENU_TABS, true)) {
						throw new InvalidArgumentException(sprintf('"%s" is not a valid option for menu tabs, use on or multiple of "%s" constants instead!', $item, ColumnTypeInterface::class . '::MENU_TAB*'));
					}
				}
				return $value;
			}
			throw new InvalidArgumentException('menuTabs may only be null or an array containing constants of ' . ColumnTypeInterface::class);
		});

		$resolver->setDefault('cacheBlockSize', 100);
		$resolver->setAllowedTypes('cacheBlockSize', 'int');

		$resolver->setDefault('pagination', false);
		$resolver->setAllowedValues('pagination', [
			true,
			false,
		]);
		$resolver->setDefault('paginationPageSize', 100);
		$resolver->setAllowedTypes('paginationPageSize', 'int');
		$resolver->setDefault('paginationAutoPageSize', false);
		$resolver->setAllowedTypes('paginationAutoPageSize', 'bool');
		$resolver->setDefault('suppressPaginationPanel', false);
		$resolver->setAllowedTypes('suppressPaginationPanel', 'bool');

		$resolver->setDefault('suppressCsvExport', true);
		$resolver->setAllowedTypes('suppressCsvExport', 'bool');

		$resolver->setDefault('suppressExcelExport', true);
		$resolver->setAllowedTypes('suppressExcelExport', 'bool');
		$resolver->setNormalizer('suppressExcelExport', static function (Options $options, $value) {
			if($value === false && !isset($options['enterpriseLicense'])) {
				throw new InvalidArgumentException('suppressExcelExport is only available in the enterprise edition. Please set a license key!');
			}
			return $value;
		});

		$resolver->setDefault('rowStyle', null);
		$resolver->setAllowedTypes('rowStyle', ['null', 'string']);
		$resolver->setDefault('getRowStyle', null);
		$resolver->setAllowedTypes('getRowStyle', ['null', 'string']);
		$resolver->setDefault('rowClass', null);
		$resolver->setAllowedTypes('rowClass', ['null', 'string']);
		$resolver->setDefault('getRowClass', null);
		$resolver->setAllowedTypes('getRowClass', ['null', 'string']);
		$resolver->setDefault('rowClassRules', null);
		$resolver->setAllowedTypes('rowClassRules', ['null', 'string']);

		$resolver->setDefault('rowSelection', null);
		$resolver->setAllowedValues('rowSelection', [null, 'single', 'multiple']);
		$resolver->setDefault('rowMultiSelectWithClick', false);
		$resolver->setAllowedTypes('rowMultiSelectWithClick', 'boolean');
		$resolver->setDefault('suppressRowClickSelection', false);
		$resolver->setAllowedTypes('suppressRowClickSelection', 'boolean');

		//Possible icons: https://www.ag-grid.com/javascript-grid-icons/
		$resolver->setDefault('icons', [
			'sortAscending'  => '<i class="fas fa-sort-amount-down"></i>',
			'sortDescending' => '<i class="fas fa-sort-amount-up"></i>',
			'menu'           => '<i class="far fa-bars" style="width: 12px;"></i>',
			'menuPin'        => '<i class="far fa-thumbtack"></i>',
			'filter'         => '<i class="far fa-filter"></i>',
			'columns'        => '<i class="far fa-columns"></i>',
			'columnMoveMove' => '<i class="far fa-arrows-alt"></i>',
			'dropNotAllowed' => '<i class="far fa-ban"></i>',
			//			'checkboxChecked'       => '<i class="far fa-check-square" style="font-size: 1.3em;"></i>',
			//			'checkboxUnchecked'     => '<i class="far fa-square" style="font-size: 1.3em;"></i>',
			//			'checkboxIndeterminate' => '<i class="far fa-minus-square" style="font-size: 1.3em;"></i>',
		]);
		$resolver->setAllowedTypes('icons', ['array', 'null']);

		$resolver->setDefault('nativeOptions', false);
	}

	protected function validateSideBarOptions(Options $options, $sidebarOption) {
		if(is_array($sidebarOption)) {
			// empty arrays are allowed, is the same as false
			if(count($sidebarOption) === 0) {
				return $sidebarOption;
			}
			// in case there is a toolPanels key, check the contents
			if(array_key_exists('toolPanels', $sidebarOption)) {
				$toolPanels = $sidebarOption['toolPanels'];
				if(is_array($toolPanels)) {
					// not having any tool panel in the sidebar is fine
					if(count($toolPanels) === 0) {
						return $sidebarOption;
					}
					foreach($toolPanels as $toolPanel) {
						if(is_string($toolPanel)) {
							if($toolPanel !== 'columns' && $toolPanel !== 'filters') {
								throw new InvalidOptionsException(sprintf('"%s" is not a valid alias for a toolPanel of the sidebar!', $toolPanel));
							}
						} else if(is_array($toolPanel)) {
							$this->validateToolPanel($options, $toolPanel);
						}
					}
					return $sidebarOption;
				}
				throw new InvalidOptionsException(sprintf('"%s" is not a valid option for the toolPanels option of the sidebar!', (string)$toolPanels));
			}
			throw new InvalidOptionsException(sprintf('The key "toolPanels" is missing in the sidebar object!'));
		}
		throw new InvalidOptionsException(sprintf('"%s" is not a valid option for the sidebar as it must be an array!', (string)$sidebarOption));
	}

	protected function validateToolPanel(Options $options, $valueToNormalize) {
		$optionsResolver = new OptionsResolver();
		$optionsResolver->setRequired('id');
		$optionsResolver->setAllowedTypes('id', 'string');

		$optionsResolver->setRequired('labelKey');
		$optionsResolver->setAllowedTypes('labelKey', 'string');

		$optionsResolver->setRequired('labelDefault');
		$optionsResolver->setAllowedTypes('labelDefault', 'string');

		$optionsResolver->setDefault('iconKey', null);
		$optionsResolver->setAllowedTypes('iconKey', ['string', 'null']);

		$optionsResolver->setDefault('toolPanel', null);
		$optionsResolver->setAllowedTypes('toolPanel', ['string', 'null']);
		$optionsResolver->setNormalizer('toolPanel', function (Options $options, $toolPanel) {
			if($toolPanel === null && $options['toolPanelFramework'] === null) {
				throw new InvalidOptionsException('You must specify a value for either "toolPanel" or "toolPanelFramework" !');
			}
			return $toolPanel;
		});

		$optionsResolver->setDefault('toolPanelFramework', null);
		$optionsResolver->setAllowedTypes('toolPanelFramework', ['string', 'null']);
		$optionsResolver->setNormalizer('toolPanelFramework', function (Options $options, $toolPanel) {
			if($toolPanel === null && $options['toolPanel'] === null) {
				throw new InvalidOptionsException('You must specify a value for either "toolPanel" or "toolPanelFramework" !');
			}
			return $toolPanel;
		});

		$optionsResolver->setDefault('toolPanelParams', null);
		$optionsResolver->setAllowedTypes('toolPanelParams', ['null', 'array']);

		$optionsResolver->resolve($valueToNormalize);
	}
}