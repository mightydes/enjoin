Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.network "private_network", ip: "192.168.56.112"
    config.vm.synced_folder ".", "/vagrant", :nfs => true

    config.vm.provider "virtualbox" do |v|
        v.name = "enjoin"
        v.customize ["modifyvm", :id, "--cpuexecutioncap", "50"]
        v.memory = 1024
    end

    config.vbguest.auto_update = true
    config.vm.provision "shell", inline: "sudo /vagrant/vagrant/bin/build.sh"
end
