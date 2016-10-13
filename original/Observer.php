<?php
class car implements SplSubject{
    private $carName;    //车的类型
    private $carState=0; //车的状态，0为关闭，1这启动车子
    private $carSpeed=0; //初始化车的速度表值
    private $Observers;  //各项车的性能观察对象

    public function __construct($Name){
        $this->carName=$Name;
        $this->Observers=new SplObjectStorage;
    }

    //启动
    public function start(){
        $this->carState=1;
        $this->notify();
    }

    //停车
    public function stop(){
        $this->carState=0;
        $this->carSpeed=0;
        $this->notify();
    }

    //加速
    public function accelerate($Acceleration){
        if(0===$this->carState){
            throw new Exception('Please start!');
        }
        if(!is_int($Acceleration) || $Acceleration<0){
            throw new Exception('The value of acceleration is invalid!');
        }
        $this->carSpeed+=$Acceleration;
        $this->notify();
    }

    //增加监测对象
    public function attach(SplObserver $observer){
        if(!$this->Observers->contains($observer)){
            $this->Observers->attach($observer);
        }
        return true;
    }

    //删除监测对象
    public function detach(SplObserver $observer){
        if(!$this->Observers->contains($observer)){
            return false;
        }
        $this->Observers->detach($observer);
        return true;
    }

    //传送对象
    public function notify(){
        foreach($this->Observers as $observer){
            $observer->update($this);
        }
    }

    public function __get($Prop){
        switch($Prop){
            case 'STATE':
                return $this->carState;
                break;
            case 'SPEED':
                return $this->carSpeed;
                break;
            case 'NAME':
                return $this->carName;
                break;
            default:
                throw new Exception($Prop.' can not be read');
        }
    }

    public function __set($Prop,$Val){
        throw new Exception($Prop.' can not be set');
    }
}

class carStateObserver implements SplObserver{
    private $SubjectState;
    public function update(SplSubject $subject){
        switch($subject->STATE){
            case 0:
                if(is_null($this->SubjectState)){
                    echo $subject->NAME.' not started'."\n";
                }else{
                    echo $subject->NAME.' stalling of engine'."\n";
                }
                $this->SubjectState=0;
                break;
            case 1:
                if(1!==$this->SubjectState){
                    echo $subject->NAME.' is starting'."\n";
                    $this->SubjectState=1;
                }
                break;
            default:
                throw new Exception('Unexpected error in carStateObserver::update()');
        }
    }
}

class carSpeedObserver implements SplObserver{
    public function update(SplSubject $subject){
        if(0!==$subject->STATE){
            echo $subject->NAME.' current speed is '.$subject->SPEED.'Kmh'."\n";
        }
    }
}

class carOverspeedObserver implements SplObserver{
    public function update(SplSubject $subject){
        if($subject->SPEED>130){
            throw new Exception('The max speed is 130, you are breaking up!'."\n");
        }
    }
}

try{
    echo "<pre>\n";
    $driver = new car('AUDIA4');
    $driver->attach(new carStateObserver);
    $driver->attach(new carSpeedObserver);
    $driver->attach(new carOverspeedObserver);
    $driver->start();
    $driver->accelerate(10);
    $driver->accelerate(30);
    $driver->stop();
    $driver->start();
    $driver->accelerate(50);
    $driver->accelerate(70);
    $driver->accelerate(100);
    $driver->accelerate(150);
    echo "</pre>\n";
}
catch(Exception$e){
    echo $e->getMessage();
}

?>
