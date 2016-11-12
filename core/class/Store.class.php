<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

class Store extends eqLogic {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	public static function event() {
		$cmd = StoreCmd::byId(init('id'));
		if (!is_object($cmd) || $cmd->getEqType() != 'Store') {
			throw new Exception(__('Commande ID store inconnu, ou la commande n\'est pas de type store : ', __FILE__) . init('id'));
		}
		$cmd->event(init('value'));
	}

	public static function cron() {
		foreach (eqLogic::byType('Store', true) as $eqLogic) {
			$autorefresh = $eqLogic->getConfiguration('autorefresh');
			if ($autorefresh != '') {
				try {
					$c = new Cron\CronExpression($autorefresh, new Cron\FieldFactory);
					if ($c->isDue()) {
						$eqLogic->refresh();
					}
				} catch (Exception $exc) {
					log::add('Store', 'error', __('Expression cron non valide pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $autorefresh);
				}
			}
		}
	}

	/*     * *********************Methode d'instance************************* */
	public function refresh() {
		try {
			foreach ($this->getCmd('info') as $cmd) {
				if ($cmd->getConfiguration('calcul') == '' || $cmd->getConfiguration('StoreAction', 0) != '0') {
					continue;
				}
				$value = $cmd->execute();
				if ($cmd->execCmd() != $cmd->formatValue($value)) {
					$cmd->setCollectDate('');
					$cmd->event($value);
				}
			}
		} catch (Exception $exc) {
			log::add('Store', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
		}
	}


    public function setCmds() {

        if (!$this->getId() || !cmd::byEqLogicIdCmdName($this->getId(), 'Up')) {
            $storeCmd = new StoreCmd();
            $storeCmd->setName(__('Up', __FILE__));
            $storeCmd->setEqLogic_id($this->id);
    		$storeCmd->setConfiguration('infoId', '');
            $storeCmd->setType('action');
            $storeCmd->setSubType('other');
            $storeCmd->setDisplay('icon', '<i class="fa fa-arrow-up"></i>');
    		$storeCmd->save();
        }

				if (!$this->getId() || !cmd::byEqLogicIdCmdName($this->getId(), 'Down')) {
		            $storeCmd = new StoreCmd();
		            $storeCmd->setName(__('Down', __FILE__));
		            $storeCmd->setEqLogic_id($this->id);
		    		$storeCmd->setConfiguration('infoId', '');
		            $storeCmd->setType('action');
		            $storeCmd->setSubType('other');
		            $storeCmd->setDisplay('icon', '<i class="fa fa-arrow-down"></i>');
		    		$storeCmd->save();
				}

				if (!$this->getId() || !cmd::byEqLogicIdCmdName($this->getId(), 'Stop')) {
		            $storeCmd = new StoreCmd();
		            $storeCmd->setName(__('Stop', __FILE__));
		            $storeCmd->setEqLogic_id($this->id);
		    		$storeCmd->setConfiguration('infoId', '');
		            $storeCmd->setType('action');
		            $storeCmd->setSubType('other');
		            $storeCmd->setDisplay('icon', '<i class="fa fa-stop"></i>');
		    		$storeCmd->save();
				}

				if (!$this->getId() || !cmd::byEqLogicIdCmdName($this->getId(), 'Favorite')) {
		    		$storeCmd = new StoreCmd();
		            $storeCmd->setName(__('Favorite', __FILE__));
		            $storeCmd->setEqLogic_id($this->id);
		    		$storeCmd->setConfiguration('infoId', '');
		            $storeCmd->setType('action');
		            $storeCmd->setSubType('other');
		            $storeCmd->setDisplay('icon', '<i class="fa fa-star-o"></i>');
		    		$storeCmd->save();
				}

				if (!$this->getId() || !cmd::byEqLogicIdCmdName($this->getId(), 'Etat')) {
		            $storeCmd = new StoreCmd();
		            $storeCmd->setName(__('Etat', __FILE__));
		            $storeCmd->setEqLogic_id($this->id);
		    		$storeCmd->setConfiguration('infoId', '');
		            $storeCmd->setType('info');
		            $storeCmd->setSubType('numeric');
		            $storeCmd->getConfiguration('StoreAction', 1);
		    		$storeCmd->save();
				}
    }


    public function postInsert() {
        $this->setCmds();
    }


    public function preSave() {
        if (!$this->getId())
          return;

        $this->setCmds();
    }

	public function postSave() {
		$refresh = $this->getCmd(null, 'refresh');
		if (!is_object($refresh)) {
			$refresh = new StoreCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setName(__('Rafraichir', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
	}

	public function copyFromEqLogic($_eqLogic_id) {
		$eqLogic = eqLogic::byId($_eqLogic_id);
		if (!is_object($eqLogic)) {
			throw new Exception(__('Impossible de trouver l\'équipement : ', __FILE__) . $_eqLogic_id);
		}
		if ($eqLogic->getEqType_name() == 'Store') {
			throw new Exception(__('Vous ne pouvez importer la configuration d\'un équipement store', __FILE__));
		}
		foreach ($eqLogic->getCategory() as $key => $value) {
			$this->setCategory($key, $value);
		}
		foreach ($eqLogic->getCmd() as $cmd_def) {
			$cmd_name = $cmd_def->getName();
			if ($cmd_name == __('Rafraichir')) {
				$cmd_name .= '_1';
			}
			$cmd = new StoreCmd();
			$cmd->setName($cmd_name);
			$cmd->setEqLogic_id($this->getId());
			$cmd->setIsVisible($cmd_def->getIsVisible());
			$cmd->setType($cmd_def->getType());
			$cmd->setUnite($cmd_def->getUnite());
			$cmd->setOrder($cmd_def->getOrder());
			$cmd->setDisplay('icon', $cmd_def->getDisplay('icon'));
			$cmd->setDisplay('invertBinary', $cmd_def->getDisplay('invertBinary'));
			foreach ($cmd_def->getTemplate() as $key => $value) {
				$cmd->setTemplate($key, $value);
			}
			$cmd->setSubType($cmd_def->getSubType());
			if ($cmd->getType() == 'info') {
				$cmd->setConfiguration('calcul', '#' . $cmd_def->getId() . '#');
				$cmd->setValue($cmd_def->getId());
			} else {
				$cmd->setValue($cmd_def->getValue());
				$cmd->setConfiguration('infoName', '#' . $cmd_def->getId() . '#');
			}
			try {
				$cmd->save();
			} catch (Exception $e) {

			}
		}
		$this->save();
	}


	public function toHtml($_version = 'dashboard') {
				//print_r($this);
        if ($this->getIsEnable() != 1) {
            return '';
        }

		log::add('Store','debug','Affichage HTML '.$this->getName());
       if ($_version == '') {
            throw new Exception(__('La version demandé ne peut être vide (mobile, dashboard ou scenario)', __FILE__));
        }
        $info = '';
        $version = jeedom::versionAlias($_version);
        $vcolor = 'cmdColor';
        if ($version == 'mobile') {
            $vcolor = 'mcmdColor';
        }
        if ($this->getPrimaryCategory() == '') {
            $cmdColor = '';
        } else {
            $cmdColor = jeedom::getConfiguration('eqLogic:category:' . $this->getPrimaryCategory() . ':' . $vcolor);
        }
        if ($this->getIsEnable()) {
            foreach ($this->getCmd(null, null, true) as $cmd) {
                $info.=$cmd->toHtml($_version, '', $cmdColor);
            }
        }

        $StoreCmd = StoreCmd::byEqLogicIdCmdName($this->getId(), 'Etat');
        $state = $StoreCmd->execCmd();

         $replace = array(
            '#id#' => $this->getId(),
            '#name#' => ($this->getIsEnable()) ? $this->getName() : '<del>' . $this->getName() . '</del>',
            '#eqLink#' => $this->getLinkToConfiguration(),
            '#category#' => $this->getPrimaryCategory(),
            '#background_color#' => $this->getBackgroundColor($version),
            '#info#' => $info,
            '#action#' => (isset($action)) ? $action : '',
            '#state#' => $state,
            '#style#' => '',
        );
        if ($_version == 'dview' || $_version == 'mview') {
            $object = $this->getObject();
            $replace['#object_name#'] = (is_object($object)) ? '(' . $object->getName() . ')' : '';
        } else {
            $replace['#object_name#'] = '';
        }
        if (($_version == 'dview' || $_version == 'mview') && $this->getDisplay('doNotShowNameOnView') == 1) {
            $replace['#name#'] = '';
            $replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
        }
        if (($_version == 'mobile' || $_version == 'dashboard') && $this->getDisplay('doNotShowNameOnDashboard') == 1) {
            $replace['#name#'] = '<br/>';
            $replace['#object_name#'] = (is_object($object)) ? $object->getName() : '';
        }

//            '#state#' => (isset($StoreCmd->getConfiguration('value')))? $StoreCmd->getConfiguration('value') : 0,
        if ($this->getIsEnable()) {
            foreach ($this->getCmd() as $cmd) {
				if ($cmd->getType() == 'action') {
					$replace[strtolower('#'.$cmd->getName().'#')] = $cmd->getId();
					$replace[strtolower('#'.$cmd->getName().'_display#')] = $cmd->getIsVisible() ? "inline-block" : "none";
					$replace[strtolower('#'.$cmd->getName().'_name#')] = ($cmd->getDisplay('icon') != '') ? $cmd->getDisplay('icon') : $cmd->getName();
				}
            }
        }

        $parameters = $this->getDisplay('parameters');
        if (is_array($parameters)) {
            foreach ($parameters as $key => $value) {
                $replace['#' . $key . '#'] = $value;
            }
        }
//
//		if (!isset(self::$_templateArray[$version])) {
//            self::$_templateArray[$version] = getTemplate('core', $version, 'eqLogic', 'Store');
//        }
//        return template_replace($replace, self::$_templateArray[$version]);
 //
        return template_replace($replace, getTemplate('core', $version, 'eqLogic', 'Store'));
    }

	/*     * **********************Getteur Setteur*************************** */
}

class StoreCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	public function dontRemoveCmd() {
		if ($this->getLogicalId() == 'refresh') {
			return true;
		}
		return false;
	}

	public function preSave() {
		if ($this->getLogicalId() == 'refresh') {
			return;
		}

		if ($this->getConfiguration('StoreAction') == 1) {
			$actionInfo = StoreCmd::byEqLogicIdCmdName($this->getEqLogic_id(), $this->getName());
			if (is_object($actionInfo)) {
				$this->setId($actionInfo->getId());
			}
		}
		if ($this->getType() == 'action') {

			if ($this->getConfiguration('infoName') == '') {
				return;
				//throw new Exception(__('Le nom de la commande info ne peut etre vide', __FILE__));
			}
			$cmd = cmd::byId(str_replace('#', '', $this->getConfiguration('infoName')));
			if (is_object($cmd)) {
				$this->setSubType($cmd->getSubType());
			} else {
				$actionInfo = StoreCmd::byEqLogicIdCmdName($this->getEqLogic_id(), $this->getConfiguration('infoName'));
				if (!is_object($actionInfo)) {
					$actionInfo = new StoreCmd();
					$actionInfo->setType('info');
					switch ($this->getSubType()) {
						case 'slider':
							$actionInfo->setSubType('numeric');
							break;
						default:
							$actionInfo->setSubType('string');
							break;
					}
				}
				$actionInfo->setConfiguration('StoreAction', 1);
				$actionInfo->setName($this->getConfiguration('infoName'));
				$actionInfo->setEqLogic_id($this->getEqLogic_id());
				$actionInfo->save();
				$this->setConfiguration('infoId', $actionInfo->getId());
			}
		} else {
			$calcul = $this->getConfiguration('calcul');
			if (strpos($calcul, '#' . $this->getId() . '#') !== false) {
				throw new Exception(__('Vous ne pouvez faire un calcul sur la valeur elle meme (boucle infinie)!!!', __FILE__));
			}
			preg_match_all("/#([0-9]*)#/", $calcul, $matches);
			$value = '';
			foreach ($matches[1] as $cmd_id) {
				if (is_numeric($cmd_id)) {
					$cmd = self::byId($cmd_id);
					if (is_object($cmd) && $cmd->getType() == 'info') {
						$value .= '#' . $cmd_id . '#';
					}
				}
			}
			preg_match_all("/variable\((.*?)\)/", $calcul, $matches);
			foreach ($matches[1] as $variable) {
				$value .= '#variable(' . $variable . ')#';
			}
			if ($value != '') {
				$this->setValue($value);
			}
		}

		if($this->getLogicalId()=='')
			$this->setLogicalId('Store');
	}

	public function postSave() {
		if ($this->getType() == 'info' && $this->getConfiguration('StoreAction', 0) == '0' && $this->getConfiguration('calcul') != '') {
			$this->event($this->execute());
		}
	}

	public function execute($_options = null) {
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->refresh();
			return;
		}
		switch ($this->getType()) {
			case 'info':
				if ($this->getConfiguration('StoreAction', 0) == '0') {
					try {
						$result = jeedom::evaluateExpression($this->getConfiguration('calcul'));
						if ($this->getSubType() == 'numeric') {
							if (is_numeric($result)) {
								$result = number_format($result, 2);
							} else {
								$result = str_replace('"', '', $result);
							}
							if (strpos($result, '.') !== false) {
								$result = str_replace(',', '', $result);
							} else {
								$result = str_replace(',', '.', $result);
							}
						}
						return $result;
					} catch (Exception $e) {
						log::add('Store', 'info', $e->getMessage());
						return jeedom::evaluateExpression($this->getConfiguration('calcul'));
					}
				}
				break;
			case 'action':
				$StoreCmd = StoreCmd::byId($this->getConfiguration('infoId'));
				if (!is_object($StoreCmd)) {
					$cmds = explode('&&', $this->getConfiguration('infoName'));
					if (is_array($cmds)) {
						foreach ($cmds as $cmd_id) {
							$cmd = cmd::byId(str_replace('#', '', $cmd_id));
							if (is_object($cmd)) {
								$cmd->execCmd($_options);
							}
						}
						return;
					} else {
						$cmd = cmd::byId(str_replace('#', '', $this->getConfiguration('infoName')));
						return $cmd->execCmd($_options);
					}
				} else {
					if ($StoreCmd->getEqType() != 'Store') {
						throw new Exception(__('La cible de la commande store n\'est pas un équipement de type store', __FILE__));
					}
					if ($this->getSubType() == 'slider') {
						$value = $_options['slider'];
					} else if ($this->getSubType() == 'color') {
						$value = $_options['color'];
					} else {
						$value = $this->getConfiguration('value');
					}
					$result = jeedom::evaluateExpression($value);
					if ($this->getSubtype() == 'message') {
						$result = $_options['title'] . ' ' . $_options['message'];
					}
					$StoreCmd->event($result);
				}
				break;
		}
	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
