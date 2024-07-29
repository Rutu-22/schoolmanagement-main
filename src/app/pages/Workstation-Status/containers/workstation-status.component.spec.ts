import { ComponentFixture, TestBed } from '@angular/core/testing';

import { WorkstationStatusComponent } from './workstation-status.component';
import { WorkstationStatusService } from '../services/workstation-status.service';
import { WorkStationMqttService } from 'src/app/common/services/workstation.mqtt.service';
import { ToastrModule, ToastrService } from 'ngx-toastr';
import { HttpClient } from '@angular/common/http';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';
import { IMqttServiceOptions, MqttModule, MqttService } from 'ngx-mqtt';
import { environment } from 'src/environments/environment';
import { Workstation } from '../models/workstation.model';
import { WindowsAuthService } from 'src/app/common/services/windows.auth.service';
import { ChangeDetectorRef } from '@angular/core';

describe('WorkstationStatusComponent', () => {
  let component: WorkstationStatusComponent;
  let fixture: ComponentFixture<WorkstationStatusComponent>;
  let httpClient: HttpClient;
  let httpController: HttpTestingController;
  const MQTT_SERVICE_OPTIONS: IMqttServiceOptions = {
    hostname: environment.mqtt.server,
    port: environment.mqtt.port,
    protocol: environment.mqtt.protocol === 'wss' ? 'wss' : 'ws',
    path: '/ws',
    clientId: 'test',
    keepalive: 1,
    clean: false,
    reconnectPeriod: 100,
    username: 'angular_admin',
    password: '123456',
  };
  beforeEach(async () => {
    localStorage.setItem('userName','test');
    await TestBed.configureTestingModule({
      imports: [
        HttpClientTestingModule,
        ToastrModule.forRoot(),
        MqttModule.forRoot(MQTT_SERVICE_OPTIONS),
      ],
      declarations: [WorkstationStatusComponent],
      providers: [
        WorkstationStatusService,
        WorkStationMqttService,
        ChangeDetectorRef,
        ToastrService,
        WindowsAuthService
      ],
    }).compileComponents();
    httpClient = TestBed.inject(HttpClient);
    httpController = TestBed.inject(HttpTestingController);
    fixture = TestBed.createComponent(WorkstationStatusComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });

  it('I able to fetch Work station status from API', () => {
    component.getWorkStations();
    const expectedResult: Workstation[] = [
      {
        fullDeviceName: 'Pavan',
        id: '1',
        loginStatus: {
          loginState: 1,
          loginTime: '',
          selectedRole: 'Operator',
          username: 'Pavan',
        },
      },
    ];
    const req = httpController.expectOne(
      'http://localhost:5087/api/Workstations'
    );
    httpController.verify();
    req.flush(expectedResult);
    httpController.verify();
    expect(component.workstation.length).toEqual(1);
  });

  it('Bind New workstation from RabbitMQ message', () => {
    component.workstation = [
      {
        fullDeviceName: 'Pavan',
        id: '1',
        loginStatus: {
          loginState: 1,
          loginTime: '',
          selectedRole: 'Operator',
          username: 'Pavan',
        },
      },
    ];
    const data: Workstation = {
      fullDeviceName: 'ABC',
      id: '1',
      loginStatus: {
        loginState: 0,
        loginTime: '',
        selectedRole: 'Operator',
        username: 'ABC',
      },
    };
    component.bindDataFromMqtt(data);

    expect(component?.workstation.length).toEqual(2);
  });

  it('Bind Logout status from RabbitMQ message', () => {
    component.workstation = [
      {
        fullDeviceName: 'Pavan',
        id: '1',
        loginStatus: {
          loginState: 1,
          loginTime: '',
          selectedRole: 'Operator',
          username: 'Pavan',
        },
      },
    ];
    const data: Workstation = {
      fullDeviceName: 'Pavan',
      id: '1',
      loginStatus: {
        loginState: 0,
        loginTime: '',
        selectedRole: 'Operator',
        username: 'Pavan',
      },
    };
    component.bindDataFromMqtt(data);

    expect(component?.workstation[0]?.loginStatus?.loginState).toEqual(0);
  });

  it('Bind Queue To Client', () => {
    component.bindQueueToClient();
    const result: Boolean = true;
    const req = httpController.expectOne(
      'http://localhost:5087/api/Workstations/BindQueueToClient'
    );
    httpController.verify();
    req.flush(result);
    httpController.verify();
    expect(result).toEqual(true);
  });
});
