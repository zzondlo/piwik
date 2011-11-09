<?php
/**
 * Piwik - Open source web analytics
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 * @version $Id$
 *
 * @category Piwik_Plugins
 * @package Piwik_VisitFrequency
 */

/**
 *
 * @package Piwik_VisitFrequency
 */
class Piwik_VisitFrequency_Controller extends Piwik_Controller
{
	function index()
	{
		$view = Piwik_View::factory('index');
		$view->graphEvolutionVisitFrequency = $this->getEvolutionGraph(true, array('nb_visits_returning') );
		$this->setSparklinesAndNumbers($view);
		echo $view->render();
	}
	
	public function getSparklines()
	{
		$view = Piwik_View::factory('sparklines');
		$this->setSparklinesAndNumbers($view);
		echo $view->render();
	}
	
	public function getEvolutionGraph( $fetch = false, $columns = false)
	{
		if(empty($columns))
		{
			$columns = Piwik_Common::getRequestVar('columns');
			$columns = Piwik::getArrayFromApiParameter($columns);
		}
		
		$documentation = Piwik_Translate('VisitFrequency_ReturningVisitsDocumentation').'<br />'
				. Piwik_Translate('General_BrokenDownReportDocumentation').'<br />'
				. Piwik_Translate('VisitFrequency_ReturningVisitDocumentation');
		
		$selectableColumns = array(
			// columns from VisitFrequency.get
			'nb_visits_returning',
			'nb_actions_returning',
			'nb_actions_per_visit_returning',
			'bounce_rate_returning',
			'avg_time_on_site_returning',
			// columns from VisitsSummary.get
			'nb_visits',
			'nb_actions',
			'nb_actions_per_visit',
			'bounce_rate',
			'avg_time_on_site'
		);
		
		$period = Piwik_Common::getRequestVar('period', false);
		if ($period == 'day')
		{
			// add number of unique (returning) visitors for period=day
			$selectableColumns = array_merge(
					array($selectableColumns[0]),
					array('nb_uniq_visitors_returning'),
					array_slice($selectableColumns, 1, -4),
					array('nb_uniq_visitors'),
					array_slice($selectableColumns, -4));
		}
		
		$view = $this->getLastUnitGraphAcrossPlugins($this->pluginName, __FUNCTION__, $columns, 
							$selectableColumns, $documentation);
		
		return $this->renderView($view, $fetch);
	}
	
	protected function setSparklinesAndNumbers($view)
	{
		$view->urlSparklineNbVisitsReturning 		= $this->getUrlSparkline( 'getEvolutionGraph', array('columns' => array('nb_visits_returning')));
		$view->urlSparklineNbActionsReturning 		= $this->getUrlSparkline( 'getEvolutionGraph', array('columns' => array('nb_actions_returning')));
		$view->urlSparklineActionsPerVisitReturning 		= $this->getUrlSparkline( 'getEvolutionGraph', array('columns' => array('nb_actions_per_visit_returning')));
		$view->urlSparklineAvgVisitDurationReturning = $this->getUrlSparkline( 'getEvolutionGraph', array('columns' => array('avg_time_on_site_returning')));
		$view->urlSparklineBounceRateReturning 	= $this->getUrlSparkline( 'getEvolutionGraph', array('columns' => array('bounce_rate_returning')));
		
		$dataTableFrequency = $this->getSummary();
		$dataRow = $dataTableFrequency->getFirstRow();
		$nbVisitsReturning = $dataRow->getColumn('nb_visits_returning');
		$view->nbVisitsReturning = $nbVisitsReturning;
		$view->nbActionsReturning = $dataRow->getColumn('nb_actions_returning');
		$view->nbActionsPerVisitReturning = $dataRow->getColumn('nb_actions_per_visit_returning');
		$view->avgVisitDurationReturning = $dataRow->getColumn('avg_time_on_site_returning');
		$nbBouncedReturningVisits = $dataRow->getColumn('bounce_count_returning');
		$view->bounceRateReturning = Piwik::getPercentageSafe($nbBouncedReturningVisits, $nbVisitsReturning);
		
	}

	protected function getSummary()
	{
		$requestString = "method=VisitFrequency.get&format=original";
		$request = new Piwik_API_Request($requestString);
		return $request->process();
	}
}
