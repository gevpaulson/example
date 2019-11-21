<?php


namespace Users;


use Bitrix\Main\Loader;
use LegalEntity\LegalEntity;

class CurrentUser {
    private static $instances = null;
    private $userId = null;
    private $userArr = null;

    public function setCurUser(int $userId = null) {
        if($userId) {
            $this->checkRequiredModules();
            $this->userId = $userId;
            return self::getInstance();
        }else{
            return false;
        }
    }

    public static function getInstance(){
        if(self::$instances === null) {
            self::$instances = new self();
        }
        return self::$instances;
    }

    final private function __construct(){
    }

    final private function __clone(){
    }

    final private function __wakeup(){
    }

    protected function checkRequiredModules(): void{
        foreach(["iblock"] as $moduleName) {
            if(!Loader::includeModule($moduleName)) {
                throw new \Tanais\BaseException();
            }
        }
    }

    private function getUserArray(int $userId){
        if($this->userArr == null) {
            $obUser = \Bitrix\Main\UserTable::getList(["select" => ["*", "UF_*"], "filter" => ["=ID" => $userId]]);
            $arUser = $obUser->fetch();
            $this->userArr = $arUser;
        }
        return $this->userArr;
    }

    public function getId(): int{
        return $this->userId;
    }

    public function getPriceCategory(): ?string{
        $selectedLegalEntity = $this->getSelectedLegalEntity();
        $obLegalEntities = \CIBlockElement::GetList(["SORT" => "ASC"], ["ID" => $selectedLegalEntity, "IBLOCK_ID" => IBLOCK_LEGAL_ENTITIES_ID], false, false, ["IBLOCK_ID", "ID", "PROPERTY_PRICE_TYPE"]);
        return $obLegalEntities->GetNext()["PROPERTY_PRICE_TYPE_VALUE"];
    }

    public function getEmail(): string{
        $arUser = $this->getUserArray($this->userId);
        $email = $arUser["EMAIL"];
        return $email;
    }

    public function getFullName(): string{
        $arUser = $this->getUserArray($this->userId);
        $fullName = $arUser["NAME"] . " " . $arUser["LAST_NAME"];
        return $fullName;
    }

    public function getLegalEntities(int $id = null): ?array{
        $companies = $id ?? $this->getUserArray($this->userId)["UF_COMPANY"];
        $legalEntities = \Bitrix\Iblock\ElementTable::getList(["select" => ["*"], "filter" => ["=ID" => $companies, "IBLOCK_ID" => "4"]]);
        while($le = $legalEntities->fetch()) {
            $arLE[] = $le;
        }
        return $arLE ?? null;
    }

    public function getLogin(): string{
        $arUser = $this->getUserArray($this->userId);
        $login = $arUser["LOGIN"];
        return $login;
    }

    public function getManager(): ?array{
        $arUser = $this->getUserArray($this->userId);
        $manager = \Bitrix\Main\UserTable::getList(["select" => ["*", "UF_*"], "filter" => ["=ID" => $arUser["UF_MANAGER_BIND"]]]);

        return $manager->fetch();
    }

    public function getName(): string{
        $arUser = $this->getUserArray($this->userId);
        $name = $arUser["NAME"];
        return $name;
    }

    public function getPhone(){
        $arUser = $this->getUserArray($this->userId);
        $phone = $arUser["WORK_PHONE"];
        return $phone;
    }

    public function getSelectedLegalEntity(): ?array{
        return current($this->getLegalEntities($_SESSION["le"]));
    }

    public function getDelivery(){
        $curLE = $this->getSelectedLegalEntity();
        $elems = \CIBlockElement::GetList(["SORT" => "ASC"],
            ["ID" => $curLE["ID"], "IBLOCK_ID" => IBLOCK_LEGAL_ENTITIES_ID],
            false, false, ["IBLOCK_ID", "ID", "PROPERTY_ADDRESS"]);
        while($arLegalEntity = $elems->GetNext()){
            $deliveries[] = ["NAME" => $arLegalEntity["PROPERTY_ADDRESS_VALUE"], "ID" => $arLegalEntity["PROPERTY_ADDRESS_VALUE_ID"]];
        }
        return $deliveries;
    }

    public function setSelectedLegalEntity($legalEntityID){
        $legalEntity = current($this->getLegalEntities($legalEntityID))["ID"] ?? current($this->getLegalEntities())["ID"];
        if($legalEntity)
            $_SESSION["le"] = $legalEntity;
        return $legalEntity ? true : false;
    }
    public function isUserPassword($password){
        $userData = $this->getUserArray($this->getId());
        $salt = substr($userData['PASSWORD'], 0, (strlen($userData['PASSWORD']) - 32));

        $realPassword = substr($userData['PASSWORD'], -32);
        $password = md5($salt.$password);

        return ($password == $realPassword);
    }
}