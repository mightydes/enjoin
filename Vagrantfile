Vagrant.configure("2") do |config|
    config.vm.box = "ubuntu/trusty64"
    config.vm.network "private_network", ip: "192.168.56.115"
    config.vm.synced_folder ".", "/vagrant", :nfs => true, :mount_options => ['actimeo=2']

    config.vm.provider "virtualbox" do |v|
        v.name = "enjoin-1x"
        #v.customize ["modifyvm", :id, "--cpuexecutioncap", "50"]
        v.memory = 1024
    end

    config.vbguest.auto_update = true
    config.vm.provision "shell", inline: "sudo /vagrant/dev/provisioning/bin/build.sh"
end
