<?php

namespace Sqquid\Sync\Model\Services;

class CategoriesSync
{

    protected $sqquidHelper;
    protected $categoryFactory;
    protected $categoryColFactory;
    protected $categories;
    protected $rootCategories;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Sqquid\Sync\Helper\Data $sqquidHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory

    )
    {
        $this->categories = [];
        $this->rootCategories = [];

        $this->categoryFactory = $categoryFactory;
        $this->categoryColFactory = $categoryColFactory;

        $this->sqquidHelper = $sqquidHelper;
        $this->rootCategory = $this->sqquidHelper->getStoreConfigValue('sqquid_general/categories/root_category');

        $this->initCategories();
    }

    /**
     * @param array $data
     * @return array
     */
    public function getOrCreateCategoryIds($data)
    {

        if (!isset($data['categories'])) {
            return [$this->rootCategory];
        }

        $categoryIds = array();

        if ($this->is_multi($data['categories'])) {
            //special case: list of categories\
            foreach ($data['categories'] as $categoryArray) {
                $categoryPath = implode("/", $categoryArray);
                $categoryIds[] = $this->getOrCreateCategory($categoryPath);
            }
        } elseif (is_array($data['categories'])) {
            $categoryPath = implode("/", $data['categories']);
            $categoryIds[] = $this->getOrCreateCategory($categoryPath);
        }

        return $categoryIds;
    }

    /**
     * Get or create category id based on a path. A path is the string equivalent of the tree node of the category
     * $categoryPath = 'Soccer/Shoes/Firm Ground'
     * this path the function will search if it exists we know the id
     * otherwise it will recursively try to get the parent id. Once it finds the parent id it creates a new category.
     * if the parent is the top root category, it stops recursion and starts creating new branch.
     *
     * @param $categoryPath
     * @return mixed
     */
    protected function getOrCreateCategory($categoryPath)
    {
        $currentCategories = $this->getCurrentCategories();

        if (empty($categoryPath) || $categoryPath == "") {
            return $this->rootCategory;
        }

        if (isset($currentCategories[$categoryPath])) {
            return $currentCategories[$categoryPath];
        }

        //create category based on the path and return the new category ID
        //get or create parent, return id
        //if category path is empty, return root id
        //once I get an id back, go ahead and create child category
        $catsArray = explode("/", $categoryPath);
        $categoryName = array_pop($catsArray);

        $parentId = $this->getOrCreateCategory(implode("/", $catsArray));
        $parentCategory = $this->categoryFactory->create()->load($parentId);

        $category = $this->categoryFactory->create();
        $category->setPath($parentCategory->getPath())
            ->setParentId($parentId)
            ->setName($categoryName)
            ->setIsActive(true)
            ->setIncludeInMenu(true)
            ->save();

        $this->categories[$categoryPath] = $category->getId();

        return $category->getId();

    }

    protected function getCurrentCategories()
    {
        if (!$this->categories) { // just in case :)
            $this->initCategories();
        }

        return $this->categories;
    }

    protected function is_multi($a)
    {
        $rv = array_filter($a, 'is_array');
        if (count($rv) > 0) return true;
        return false;
    }

    /**
     * Make an array of all categories by name and path. key = path, value = id
     * The main purpose of this structure is to allow for easy search of categories
     * see if they exist and if not, create one in the correct node easily
     * Exmple:
     * Array() = {
     * 'Footwear' => 2,
     * 'Footwear/Firm Ground Soccer Shoes' => 3,
     * 'Clearance' => 4,
     * 'Clearance/Soccer Shoes' => 5,
     * 'Clearance/Soccer Shoes/Firm Groun' => 6
     * }
     *
     * @return $this
     */
    public function initCategories()
    {
        $collection = $this->categoryColFactory->create()->addNameToResult();
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        foreach ($collection as $category) {
            $structure = preg_split('#/+#', $category->getPath());
            $pathSize = count($structure);
            if ($pathSize > 1) {
                $path = [];
                for ($i = 1; $i < $pathSize; $i++) {
                    $path[] = $collection->getItemById($structure[$i])->getName();
                }

                $this->rootCategories[$category->getId()] = array('name' => array_shift($path), 'id' => $structure[0]);

                if ($pathSize > 2) {
                    $this->categories[implode('/', $path)] = $category->getId();
                }
            }
        }
        return $this;
    }


}

