import { TestBed } from '@angular/core/testing';

import { CasteService } from './caste.service';

describe('CasteService', () => {
  let service: CasteService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(CasteService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
