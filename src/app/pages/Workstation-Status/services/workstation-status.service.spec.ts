import { Workstation } from './../models/workstation.model';
import { HttpClient } from '@angular/common/http';
import { TestBed } from '@angular/core/testing';
import {
  HttpClientTestingModule,
  HttpTestingController,
} from '@angular/common/http/testing';

import { WorkstationStatusService } from './workstation-status.service';
describe('WorkstationStatusService', () => {
  let httpClient: HttpClient;
  let service: WorkstationStatusService;
  let httpController: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      imports: [HttpClientTestingModule],
      providers:[WorkstationStatusService]
    });
    httpClient = TestBed.inject(HttpClient);
    service = TestBed.inject(WorkstationStatusService);
    httpController = TestBed.inject(HttpTestingController);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });

  it('should return value from observable', () => {
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
    service.getWorkStationStatus().subscribe((value) => {
      expect(value).toEqual(expectedResult);
    });
    const req = httpController.expectOne('http://localhost:5087/api/Workstations');
    httpController.verify();
    req.flush(expectedResult);
    httpController.verify();
  });
});
