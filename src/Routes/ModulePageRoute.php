<?php namespace Fgta5\Framework\Routes;

use AgungDhewe\PhpLogger\Log;
use AgungDhewe\Webservice\IRouteHandler;
use AgungDhewe\Webservice\Routes\PageRoute;
use AgungDhewe\Webservice\Database;
use AgungDhewe\Webservice\ServiceRoute;
use AgungDhewe\Webservice\WebTemplate;

use Fgta5\Framework\IDefaultModule;
use Fgta5\Framework\IModulePage;
use Fgta5\Framework\ModulePage;
use Fgta5\Framework\TemplateContainer;

class ModulePageRoute extends PageRoute implements IRouteHandler {
	function __construct(string $urlreq) {
		parent::__construct($urlreq); // contruct dulu parentnya
	}


	// #[Override]
	public function route(?array $param = []) : void {
		Log::info("Route Module Page $this->urlreq");

		if (array_key_exists('httpheader', $param)) {
			$httpheader = $param['httpheader'];
			header($httpheader);
		}

		try {

			// Koneksi database
			Database::Connect();

			// Cek Session


			

			// Cek Class Exists
			$requestedModulePageClass = ServiceRoute::getRequestedParameter('module/page/', $this->urlreq);
			$modulePageClass = str_replace('/', '\\', $requestedModulePageClass);



			// DefaultModulePageClass:  <vendor>\<name>\ModulePage
			$ns = ServiceRoute::getModuleNamespace($modulePageClass);
			$defclass = implode('\\', [$ns, 'ModulePage']);
			// cek apakah Default Class exists
			Log::info("loading class $defclass");	
			if (!class_exists($defclass)) {
				$errmsg = Log::error("Class '$defclass' is not exists");
				throw new \Exception($errmsg, 500);
			}

			
			// loading class, tidak ikut PSR4
			$path = $defclass::GetModulePagePath($modulePageClass);
			if (!is_file($path)) {
				$errmsg = Log::error("File '$path' is not exists");
				throw new \Exception($errmsg, 500);
			}
			require_once $path;

			

			Log::info("loading class $modulePageClass");	
			if (!class_exists($modulePageClass)) {
				$errmsg = Log::error("Class Module '$modulePageClass' is not exists");
				throw new \Exception($errmsg, 500);
			}

			// cek apakah defaultClass implement IDefaultModule
			if (!in_array(IDefaultModule::class, class_implements($defclass))) {
				$errmsg = Log::error("Class '$defclass' not implements IDefaultModule");
				throw new \Exception($errmsg, 500);
			}



			// cek apakah modulePageClass implement IModulePage 
			if (!in_array(IModulePage::class, class_implements($modulePageClass))) {
				$errmsg = Log::error("Class '$modulePageClass' not implements IModulePage");
				throw new \Exception($errmsg, 500);
			}

			// cek apakah modulePageClass subclass dari ModulePage
			if (!is_subclass_of($modulePageClass, ModulePage::class)) {
				$errmsg = Log::error("Class '$modulePageClass' not subclass of ModulePage");
				throw new \Exception($errmsg, 500);
			}


			$module = new $modulePageClass();
			$tpl = $module->getTemplate(['modulepageclass'=>$modulePageClass]);
			self::SetTemplate($tpl);
			
			

			
			// Load Module
			$content = "";
			$params = [];
			try {
				// cek apakah template valid
				if (!WebTemplate::Validate($tpl)) {
					$tplclassname = get_class($tpl);
					$errmsg = Log::error("Class '$tplclassname' not subclass of WebTemplate");
					throw new \Exception($errmsg, 500);
				}

				ob_start();
				$module->LoadPage($params);
				$data = $module->getData();
				self::SetData($data);

				// set data template setelah selesai load page
				$title = $module->getTitle();
				$tpl->setTitle($title);


				$content = ob_get_contents();
			} catch (\Exception $ex) {
				$errmsg = Log::error($ex->getMessage());
				throw new \Exception($errmsg, $ex->getCode());
			} finally {
				ob_end_clean();
			}


			$tpl->Render($content);
		
		} catch (\Exception $ex) {
			throw $ex;
		}

	}
}