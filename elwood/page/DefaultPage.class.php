<?php
	namespace elwood\page;
	
	class DefaultPage extends Page
	{
		public function __construct(array &$request)
		{
			$this->request =& $request;
		}
		
		public function name()
		{
			/** page name */
			return "Default Page";
		}
		
		public function head()
		{
			/** header content (linking to external .css or .js files, for example */
		}
		
		public function style()
		{
			/** css content */
		}
		
		public function content()
		{
			/** page content */
			return "This is the default page.";
		}
		
		public function isRestricted()
		{
			return false;
		}
	}
?>