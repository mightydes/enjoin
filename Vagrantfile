# Read .env file:
env = {}
File.read(".env").split("\n").each do |ef|
  env[ef.split("=")[0]] = ef.split("=")[1]
end

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/trusty64"
  config.vm.network "private_network", ip: "192.168.56.115"
  config.vm.synced_folder ".", "/vagrant", type: "nfs",
    mount_options: ["rw", "vers=3", "tcp", "fsc" ,"actimeo=1"],
    linux__nfs_options: ["rw", "no_subtree_check", "all_squash", "async"]

  config.vm.provider "virtualbox" do |v|
    v.gui = false
    v.name = "enjoin-1x"
    v.memory = 2048
    v.cpus = 2
    v.customize ["modifyvm", :id, "--cpuexecutioncap", "75"]
    v.customize ["modifyvm", :id, "--ioapic", "on"]
  end

  config.vbguest.auto_update = true
  config.vm.provision "shell", inline: "sudo /vagrant/dev/provisioning/bin/build.sh"
end
