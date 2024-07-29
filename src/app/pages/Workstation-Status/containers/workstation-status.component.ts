import { Workstation } from './../models/workstation.model';
import { ChangeDetectorRef, Component, OnInit } from '@angular/core';
import { WorkstationStatusService } from 'src/app/pages/Workstation-Status/services/workstation-status.service';
import { Subscription } from 'rxjs';
import { WorkStationMqttService } from 'src/app/common/services/workstation.mqtt.service';
import { ToastrService } from 'ngx-toastr';

@Component({
  selector: 'app-workstation-status',
  templateUrl: './workstation-status.component.html',
  styleUrls: ['./workstation-status.component.scss'],
})
export class WorkstationStatusComponent implements OnInit {
  clientId: string = '';
  workstation: Workstation[] = [];
  subscription: Subscription;
  workstationType: Workstation;
  constructor(
    private workStationService: WorkstationStatusService,
    private readonly workstationMqttService: WorkStationMqttService,
    private cd: ChangeDetectorRef,
    private toastr: ToastrService
  ) {}

  ngOnInit(): void {

  }


  getWorkStations() {

  }

  ngOnDestroy(): void {
    if (this.subscription) {
      this.subscription.unsubscribe();
    }
  }

}
